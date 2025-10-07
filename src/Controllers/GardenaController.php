<?php

namespace GardenaProxy\Controllers;

use GardenaProxy\Data\Models\AbstractDevice;
use GardenaProxy\Data\Models\Controller;
use GardenaProxy\Data\Models\Sensor;
use GardenaProxy\Data\Models\Valve;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use stdClass;

class GardenaController extends AbstractController
{

    public function getDevices(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $from = $queryParams['from'] ?? null;
        if ($from) {
            if ($from < 1_000_000_000) {
                $from = time() - $from;
            }
            $devices = $this->db()->getDevicesModifiedAfter($from);
        } else {
            $devices = $this->db()->getAllDevices();
        }

        $data = array_map(function($device) {
            return (array)$device;
        }, $devices);

        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function postCallbacks(Request $request, Response $response): Response
    {
        if (($request->getParsedBody()->data->type ?? null) !== 'WEBHOOK') {
            return $response->withStatus(204);
        }

        $locationId = $request->getParsedBody()->data->attributes->location_id ?? null;
        $events = $request->getParsedBody()->data->attributes->events ?? [];
        $devices = $this->extractDevices($events);

        if (count($devices) > 0) {
            foreach ($devices as $device) {
                $this->db()->saveDevice($device);
            }
        }

        $data = [
            'status' => 'success',
            'locationId' => $locationId,
            'events' => count($events),
            'devices' => count($devices),
        ];
        
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    protected function extractDevices(array $events): array
    {
        $devices = [];
        foreach ($events as $event) {
            $device = $this->constructDevice($event);
            if ($device) {
                $devices[$device->id] = $device;
            }
        }
        return $devices;
    }

    protected function constructDevice(stdClass $event): ?AbstractDevice
    {
        $classMap = [
            Controller::class,
            Sensor::class,
            Valve::class,
        ];
        foreach ($classMap as $class) {
            if ($device = $class::construct($event)) {
                return $device;
            }
        }
        return null;
    }
}