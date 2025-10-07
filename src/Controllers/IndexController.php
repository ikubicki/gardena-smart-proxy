<?php

namespace GardenaProxy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractController
{

    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode(['message' => 'Not found']));   
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    }

    public function getVersion(Request $request, Response $response): Response
    {
        $data = [
            'version' => $this->config->get('VERSION', 'dev'),
        ];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}