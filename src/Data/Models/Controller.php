<?php

namespace GardenaProxy\Data\Models;

use stdClass;

class Controller extends AbstractDevice
{
    public string $id = '';
    public string $name = '';
    public string $serial = '';
    public string $modelType = '';
    public string $rfLinkState = '';
    public int $rfLinkLevel = 0;

    public static function construct(stdClass $data): ?self
    {
        if (empty($data->id) || $data->type !== 'COMMON' || stripos($data->attributes->modelType->value ?? '', 'control') === false) {
            return null;
        }
        if (self::get($data->id)) {
            return self::get($data->id);
        }

        $controller = new self();
        $controller->id = $data->id ?? '';
        $controller->name = $data->attributes->name->value ?? '';
        $controller->serial = $data->attributes->serial->value ?? '';
        $controller->modelType = $data->attributes->modelType->value ?? '';
        $controller->rfLinkState = $data->attributes->rfLinkState->value ?? '';
        $controller->rfLinkLevel = $data->attributes->rfLinkLevel->value ?? 0;
        return self::register($controller);
    }
}
