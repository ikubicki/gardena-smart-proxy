<?php

namespace GardenaProxy\Data\Models;

use stdClass;

class Valve extends AbstractDevice
{
    public string $id = '';
    public string $name = '';
    public string $activity = '';
    public string $state = '';

    public static function construct(stdClass $data): ?self
    {
        if (empty($data->id) || $data->type !== 'VALVE') {
            return null;
        }
        if (self::get($data->id)) {
            return self::get($data->id);
        }

        $valve = new self();
        $valve->id = $data->id ?? '';
        $valve->name = $data->attributes->name->value ?? '';
        $valve->activity = $data->attributes->activity->value ?? '';
        $valve->state = $data->attributes->state->value ?? '';
        return self::register($valve);
    }
}