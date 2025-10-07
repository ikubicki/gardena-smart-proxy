<?php

namespace GardenaProxy\Middleware;

use GardenaProxy\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class BasicAuthMiddleware implements MiddlewareInterface
{
    protected string $username;
    protected string $password;
    protected array $excludedRoutes = [];

    public function __construct(Config $config)
    {
        $this->username = $config->get('BASIC_AUTH_USER', 'gardena');
        $this->password = $config->get('BASIC_AUTH_PASS', 'proxy');
        $this->excludedRoutes = explode(',', $config->get('BASIC_AUTH_EXCLUDE'));
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $path = $request->getUri()->getPath();
        if (in_array($path, $this->excludedRoutes)) {
            return $handler->handle($request);
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (strpos($authHeader, 'Basic ') === 0) {
            $encodedCredentials = substr($authHeader, 6);
            $decodedCredentials = base64_decode($encodedCredentials);
            if ($decodedCredentials) {
                [$user, $pass] = explode(':', $decodedCredentials, 2);
                if ($user === $this->username && $pass === $this->password) {
                    return $handler->handle($request);
                }
            }
        }

        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['message' => 'Unauthorized']));
        return $response
            ->withHeader('WWW-Authenticate', 'Basic realm="Gardena Proxy"')
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}