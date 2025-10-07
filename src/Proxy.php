<?php

namespace GardenaProxy;

use Slim\App as SlimApp;
use GardenaProxy\Controllers\CallbacksController;
use GardenaProxy\Controllers\IndexController;
use GardenaProxy\Controllers\ProxyController;
use GardenaProxy\Middleware\BasicAuthMiddleware;
use GardenaProxy\Middleware\JsonBodyParserMiddleware;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Request;
use Throwable;

class Proxy
{
    protected SlimApp $app;
    protected Config $config;

    public function __construct(SlimApp $app, Config $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    public function setup(): self
    {
        $this->addMiddleware();
        $this->addRoutes();
        return $this;
    }

    public function run(): self
    {
        $this->app->run();
        return $this;
    }

    protected function addMiddleware()
    {
        $this->app->addRoutingMiddleware();
        $this->app->add(JsonBodyParserMiddleware::class);
        $this->app->add(new BasicAuthMiddleware($this->config));
        $errorMiddleware = $this->app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setDefaultErrorHandler([$this, 'customErrorHandler']);
    }

    public function customErrorHandler(Request $request, Throwable $exception): ResponseInterface
    {
        $payload = ['message' => $exception->getMessage()];
        if ($exception instanceof HttpNotFoundException) {
            $payload['message'] = sprintf('Resource not found: %s %s', $request->getMethod(), $request->getRequestTarget());
            $statusCode = 404;
        } else {
            $statusCode = 500;
        }

        $response = $this->app->getResponseFactory()->createResponse();
        $response->getBody()->write(
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    protected function addRoutes()
    {

        $indexController = new IndexController($this->config);
        $callbacksController = new CallbacksController($this->config);
        $proxyController = new ProxyController($this->config);

        // Routes
        $this->app->get('/', [$indexController, 'index']);
        $this->app->get('/version', [$indexController, 'getVersion']);
        $this->app->get('/favicon.ico', function ($request, $response) {
            return $response->withStatus(204);
        });

        // Callbacks endpoints
        $this->app->post('/callbacks', [$callbacksController, 'postCallbacks']);
        $this->app->get('/devices', [$callbacksController, 'getDevices']);

        // Proxy endpoints
        $this->app->get('/proxy/locations', [$proxyController, 'getLocations']);
        $this->app->get('/proxy/locations/{locationId}', [$proxyController, 'getLocation']);
        $this->app->post('/proxy/webhook', [$proxyController, 'createWebhook']);
        $this->app->get('/proxy/webhook/{locationId}', [$proxyController, 'getLocationWebhook']);
        $this->app->put('/proxy/command/{deviceId}', [$proxyController, 'sendCommandToDevice']);
    }
}
