<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\AnalyticsController;

$app->get('/api/v1/satisfaction', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getSatisfaction($request, $response);
});

$app->get('/api/v1/satisfaction/retention', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getRetention($request, $response);
});
