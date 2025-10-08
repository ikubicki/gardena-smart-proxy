<?php

namespace GardenaProxy\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProxyController extends AbstractController
{

    public function getLocations(Request $request, Response $response): Response
    {
        $apiResponse = $this->guzzle()->get('/v2/locations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authenticate(),
                'X-Api-Key' => $this->config->get('GARDENA_API_CLIENT_ID'),
            ],
        ]);
        $data = json_decode((string) $apiResponse->getBody(), true);
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($apiResponse->getStatusCode());
    }

    public function getLocation(Request $request, Response $response, array $args): Response
    {
        $locationId = $args['locationId'];
        $apiResponse = $this->guzzle()->get("/v2/locations/{$locationId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authenticate(),
                'X-Api-Key' => $this->config->get('GARDENA_API_CLIENT_ID'),
            ],
        ]);
        $data = json_decode((string) $apiResponse->getBody(), true);
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($apiResponse->getStatusCode());
    }

    public function getLocationWebhook(Request $request, Response $response, array $args): Response
    {
        $locationId = $args['locationId'];
        $apiResponse = $this->guzzle()->get("/v2/webhook/{$locationId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authenticate(),
                'X-Api-Key' => $this->config->get('GARDENA_API_CLIENT_ID'),
            ],
        ]);
        $data = json_decode((string) $apiResponse->getBody(), true);
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($apiResponse->getStatusCode());
    }

    public function createWebhook(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        if (!$body) {
            throw new Exception('Invalid payload');
        }
        $apiResponse = $this->guzzle()->post("/v2/webhook", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authenticate(),
                'X-Api-Key' => $this->config->get('GARDENA_API_CLIENT_ID'),
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);
        $data = json_decode((string) $apiResponse->getBody(), true);
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($apiResponse->getStatusCode());
    }

    public function sendCommandToDevice(Request $request, Response $response, array $args): Response
    {
        $deviceId = urlencode($args['deviceId']);
        $body = $request->getParsedBody();
        $apiResponse = $this->guzzle()->put("/v2/command/{$deviceId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authenticate(),
                'X-Api-Key' => $this->config->get('GARDENA_API_CLIENT_ID'),
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);
        $data = json_decode((string) $apiResponse->getBody(), true);
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($apiResponse->getStatusCode());
    }

    protected string $accessToken = '';

    protected function authenticate(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        $this->accessToken = $this->db()->getCache('access_token', '');
        if (!$this->accessToken) {
            $authUrl = $this->config->get('GARDENA_AUTH_URL', 'https://api.authentication.husqvarnagroup.dev/v1/oauth2/token');
            $response = $this->guzzle()->post($authUrl, [
                'form_params' => [
                    'client_id' => $this->config->get('GARDENA_API_CLIENT_ID'),
                    'client_secret' => $this->config->get('GARDENA_API_CLIENT_SECRET'),
                    'grant_type' => 'client_credentials',
                ],
            ]);
            $data = json_decode((string)$response->getBody(), true);
            if (isset($data['access_token'])) {
                $this->accessToken = $data['access_token'];
                $this->db()->setCache('access_token', $this->accessToken, 21600); // cache for 6 hours
            } else {
                throw new \Exception('Failed to authenticate with Gardena API');
            }
        }
        return $this->accessToken;
    }
}
