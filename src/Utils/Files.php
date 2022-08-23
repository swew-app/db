<?php

declare(strict_types=1);

namespace Swew\Db\Utils;

final class Files
{
    private function __construct()
    {
    }

    public static function getFilesByPattern(string $filePattern): array
    {
        $paths = self::makeSubPathPatterns([$filePattern]);

        $files = [];

        foreach ($paths as $path) {
            $files = array_merge($files, glob($path, GLOB_ERR));
        }

        // filter vendor
        $files = array_filter($files, function (string $path) {
            return !str_contains($path, 'vendor');
        });

        return array_unique($files);
    }

    private static function makeSubPathPatterns(array $paths): array
    {
        $added = [];

        foreach ($paths as $path) {
            if (str_contains($path, '**')) {
                $added[] = str_replace('**', '', $path);
                $added[] = str_replace('**', '*', $path);
                $added[] = str_replace('**', '*/*', $path);
                $added[] = str_replace('**', '*/*/*', $path);
                $added[] = str_replace('**', '*/*/*/*', $path);
                $added[] = str_replace('**', '*/*/*/*/*', $path);
                $added[] = str_replace('**', '*/*/*/*/*/*', $path);
            }
        }

        return array_merge($paths, $added);
    }
}
