<?php

use GardenaProxy\Config;

Config::set('SQLITE_DIR', __DIR__ . '/sqlite');
Config::set('BASIC_AUTH_EXCLUDE', implode(',', ['/favicon.ico', '/callbacks']));
Config::set('BASIC_AUTH_USER', '<Your Basic Auth User>');
Config::set('BASIC_AUTH_PASSWORD', '<Your Basic Auth Password>');
Config::set('GARDENA_API_URL', 'https://api.smart.gardena.dev');
Config::set('GARDENA_API_CLIENT_ID', '<Your Gardena API Client ID>');
Config::set('GARDENA_API_CLIENT_SECRET', '<Your Gardena API Client Secret>');
// Config::set('GARDENA_AUTH_URL', 'https://api.authentication.husqvarnagroup.dev/v1/oauth2/token');
