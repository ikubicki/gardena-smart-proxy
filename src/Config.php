<?php

namespace GardenaProxy;

class Config
{
    public function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        return $value ? $value : $default;
    }

    public static function set(string $key, string $value): void
    {
        putenv("$key=$value");
    }
}