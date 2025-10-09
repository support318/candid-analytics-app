<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\AuthController;

$app->post('/api/auth/login', function (Request $request, Response $response) use ($container) {
    $controller = new AuthController($container);
    return $controller->login($request, $response);
});

$app->post('/api/auth/refresh', function (Request $request, Response $response) use ($container) {
    $controller = new AuthController($container);
    return $controller->refresh($request, $response);
});

$app->post('/api/auth/logout', function (Request $request, Response $response) use ($container) {
    $controller = new AuthController($container);
    return $controller->logout($request, $response);
});
