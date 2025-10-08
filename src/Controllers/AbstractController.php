<?php

namespace GardenaProxy\Controllers;

use GardenaProxy\Config;
use GardenaProxy\Data\SQLite;
use GuzzleHttp\Client as GuzzleClient;

abstract class AbstractController
{
    protected Config $config;
    protected SQLite $db;
    protected GuzzleClient $guzzle;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function db(): SQLite
    {
        if (!isset($this->db)) {
            $this->db = new SQLite($this->config);
        }
        return $this->db;
    }
    
    public function guzzle(): GuzzleClient
    {
        if (!isset($this->guzzle)) {
            $this->guzzle = new GuzzleClient($this->getGuzzleOptions());
        }
        return $this->guzzle;
    }

    protected function getGuzzleOptions(): array
    {
        return [
            'base_uri' => $this->config->get('GARDENA_API_URL'),
            'timeout'  => $this->config->get('GARDENA_API_TIMEOUT', 5),
            'http_errors' => false,
        ];
    }
}