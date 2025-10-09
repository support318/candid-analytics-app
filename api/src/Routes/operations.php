<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\AnalyticsController;

$app->get('/api/v1/operations', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getOperations($request, $response);
});
