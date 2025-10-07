<?php

namespace GardenaProxy\Data\Models;

abstract class AbstractDevice
{

    public string $id = '';
    public readonly string $type;
    public int $timestamp;

    public function __construct()
    {
        $this->type = (new \ReflectionClass($this))->getShortName();
        $this->timestamp = time();
    }

    protected static array $registry = [];

    public static function register(AbstractDevice $device): AbstractDevice
    {
        return self::$registry[$device->id] = $device;
    }

    public static function get(string $id): ?AbstractDevice
    {
        return self::$registry[$id] ?? null;
    }
}