<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\AnalyticsController;

$app->get('/api/v1/sales-funnel', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getSalesFunnel($request, $response);
});

$app->get('/api/v1/lead-sources', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getLeadSources($request, $response);
});
