<?php

namespace GardenaProxy\Data\Models;

use stdClass;

class Sensor extends AbstractDevice
{
    public string $id = '';
    public string $name = '';
    public int $humidity = 0;
    public int $temperature = 0;
    public int $batteryLevel = 0;
    public string $serial = '';
    public string $modelType = '';
    public string $rfLinkState = '';
    public int $rfLinkLevel = 0;

    public static function construct(stdClass $data): ?self
    {
        if (empty($data->id)) {
            return null;
        }
        if ($data->type !== 'SENSOR') {
            if ($data->type !== 'COMMON' || stripos($data->attributes->modelType->value ?? '', 'sensor') === false) {
                return null;
            }
        }
        /** @var Sensor $sensor */
        $sensor = self::get($data->id);
        if (!$sensor) {
            $sensor = new self();
            $sensor->id = $data->id ?? '';
            self::register($sensor);
        }
        $sensor->name = $data->attributes->name->value ?? $sensor->name ?: '';
        $sensor->humidity = $data->attributes->soilHumidity->value ?? $sensor->humidity ?: 0;
        $sensor->temperature = $data->attributes->soilTemperature->value ?? $sensor->temperature ?: 0;
        $sensor->batteryLevel = $data->attributes->batteryLevel->value ?? $sensor->batteryLevel ?: 0;
        $sensor->serial = $data->attributes->serial->value ?? $sensor->serial ?: '';
        $sensor->modelType = $data->attributes->modelType->value ?? $sensor->modelType ?: '';
        $sensor->rfLinkState = $data->attributes->rfLinkState->value ?? $sensor->rfLinkState ?: '';
        $sensor->rfLinkLevel = $data->attributes->rfLinkLevel->value ?? $sensor->rfLinkLevel ?: 0;
        return $sensor;
    }
}