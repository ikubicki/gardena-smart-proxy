<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

use GardenaProxy\Proxy;
use GardenaProxy\Config;
use Slim\Factory\AppFactory;

$config = new Config();
$app = AppFactory::create();
$app->setBasePath('');
$proxy = new Proxy($app, $config);
$proxy->setup();
$proxy->run();
