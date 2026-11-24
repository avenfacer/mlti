<?php
/**
 * Helper sederhana untuk membaca file .env tanpa dependency tambahan (Composer).
 */

if (!function_exists('load_env')) {
    function load_env(string $path): void {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string {
        $value = getenv($key);
        return ($value !== false && $value !== '') ? $value : $default;
    }
}
