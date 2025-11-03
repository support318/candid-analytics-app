<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\TwoFactorController;

// Get 2FA status
$app->get('/api/v1/users/me/2fa/status', function (Request $request, Response $response) use ($container) {
    $jwt = $container->get('jwt');
    $controller = new TwoFactorController($container);
    return $controller->status($request, $response, $jwt);
});

// Setup 2FA (generate secret and QR code)
$app->post('/api/v1/users/me/2fa/setup', function (Request $request, Response $response) use ($container) {
    $jwt = $container->get('jwt');
    $controller = new TwoFactorController($container);
    return $controller->setup($request, $response, $jwt);
});

// Verify 2FA code and enable 2FA
$app->post('/api/v1/users/me/2fa/verify', function (Request $request, Response $response) use ($container) {
    $jwt = $container->get('jwt');
    $controller = new TwoFactorController($container);
    return $controller->verify($request, $response, $jwt);
});

// Disable 2FA
$app->post('/api/v1/users/me/2fa/disable', function (Request $request, Response $response) use ($container) {
    $jwt = $container->get('jwt');
    $controller = new TwoFactorController($container);
    return $controller->disable($request, $response, $jwt);
});
