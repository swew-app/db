<?php

declare(strict_types=1);

use Swew\Db\Migrator;

beforeEach(function () {
    $GLOBALS['migrationDirectory'] = sys_get_temp_dir() . '/swew-migrations-' . bin2hex(random_bytes(8));
    mkdir($GLOBALS['migrationDirectory']);
});

afterEach(function () {
    foreach (glob($GLOBALS['migrationDirectory'] . '/*') as $path) {
        unlink($path);
    }
    rmdir($GLOBALS['migrationDirectory']);
});

it('orders migrations numerically, applies once, and rolls back the latest', function () {
    $directory = $GLOBALS['migrationDirectory'];
    file_put_contents($directory . '/10_seed.sql', "-- +swew Up\nINSERT INTO example VALUES (1, 'a;b');\n-- +swew Down\nDELETE FROM example;");
    file_put_contents($directory . '/2_table.sql', "-- +swew Up\nCREATE TABLE example (id INTEGER, name TEXT);\n-- +swew Down\nDROP TABLE example;");
    $pdo = getPDO();
    $migrator = new Migrator($directory, $pdo);
    expect(array_column($migrator->status(), 'applied'))->toBe([false, false]);
    expect($migrator->up())->toBe([2, 10]);
    expect($migrator->up())->toBe([]);
    expect($pdo->query('SELECT name FROM example')->fetchColumn())->toBe('a;b');
    expect($migrator->down())->toBe(10);
    expect($pdo->query('SELECT COUNT(*) FROM example')->fetchColumn())->toBe(0);
    expect($migrator->down())->toBe(2);
    expect($migrator->down())->toBe(null);
    expect($migrator->up())->toBe([2, 10]);
});

it('rolls back failed SQL and version tracking, including failed down migrations', function () {
    $directory = $GLOBALS['migrationDirectory'];
    $path = $directory . '/1_table.sql';
    file_put_contents($path, "-- +swew Up\nCREATE TABLE example (id INTEGER);\nINVALID SQL;\n-- +swew Down\nDROP TABLE example;");
    $pdo = getPDO();
    $migrator = new Migrator($directory, $pdo);
    expect(fn () => $migrator->up())->toThrow(RuntimeException::class);
    expect($pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE name = 'example'")->fetchColumn())->toBe(0);
    expect($migrator->status()[0]['applied'])->toBe(false);
    file_put_contents($path, "-- +swew Up\nCREATE TABLE example (id INTEGER);\n-- +swew Down\nDROP TABLE example;\nINVALID SQL;");
    expect($migrator->up())->toBe([1]);
    expect(fn () => $migrator->down())->toThrow(RuntimeException::class);
    expect($migrator->status()[0]['applied'])->toBe(true);
    expect($pdo->query('SELECT COUNT(*) FROM example')->fetchColumn())->toBe(0);
    expect($pdo->inTransaction())->toBe(false);
});

it('supports statement blocks and explicit nontransactional migrations', function () {
    $directory = $GLOBALS['migrationDirectory'];
    file_put_contents($directory . '/1_trigger.sql', "-- +swew NO TRANSACTION\n-- +swew Up\nCREATE TABLE example (id INTEGER);\n-- +swew StatementBegin\nCREATE TRIGGER fill AFTER INSERT ON example BEGIN UPDATE example SET id = 2; END;\n-- +swew StatementEnd\nINSERT INTO example VALUES (1);\n-- +swew Down\nDROP TABLE example;");
    $pdo = getPDO();
    $migrator = new Migrator($directory, $pdo);
    expect($migrator->up())->toBe([1]);
    expect($pdo->query('SELECT id FROM example')->fetchColumn())->toBe(2);
    expect($migrator->down())->toBe(1);
});

it('rejects invalid versions, annotations, missing files, and out-of-order migrations', function () {
    $directory = $GLOBALS['migrationDirectory'];
    $path = $directory . '/2_table.sql';
    $migrator = new Migrator($directory, getPDO());
    foreach (["-- +swew Down\n-- +swew Up", "-- +swew Up\nSELECT 1;", "-- +swew Up\n-- +swew StatementBegin\n-- +swew Down"] as $invalid) {
        file_put_contents($path, $invalid);
        expect(fn () => $migrator->up())->toThrow(RuntimeException::class);
    }
    file_put_contents($path, "-- +swew Up\nSELECT 1;\n-- +swew Down\nSELECT 1;");
    file_put_contents($directory . '/02_duplicate.sql', file_get_contents($path));
    expect(fn () => $migrator->up())->toThrow(RuntimeException::class);
    unlink($directory . '/02_duplicate.sql');
    $migrator->up();
    file_put_contents($directory . '/1_older.sql', file_get_contents($path));
    expect(fn () => $migrator->up())->toThrow(RuntimeException::class);
    unlink($path);
    expect(fn () => $migrator->down())->toThrow(RuntimeException::class);
});

it('creates SQL templates, validates names, and preserves caller transactions', function () {
    $directory = $GLOBALS['migrationDirectory'];
    $path = Migrator::create($directory, 'create_users');
    expect(file_get_contents($path))->toBe("-- +swew Up\n\n-- +swew Down\n");
    expect(fn () => Migrator::create($directory, '../unsafe'))->toThrow(InvalidArgumentException::class);
    $pdo = getPDO();
    $pdo->beginTransaction();
    expect(fn () => (new Migrator($directory, $pdo))->up())->toThrow(RuntimeException::class);
    expect($pdo->inTransaction())->toBe(true);
    $pdo->rollBack();
});
