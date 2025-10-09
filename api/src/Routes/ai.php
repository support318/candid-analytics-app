<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\AnalyticsController;

$app->get('/api/v1/ai/insights', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getAiInsights($request, $response);
});

$app->post('/api/v1/ai/predict-lead', function (Request $request, Response $response) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->predictLead($request, $response);
});

$app->get('/api/v1/ai/similar-clients/{clientId}', function (Request $request, Response $response, array $args) use ($container) {
    $controller = new AnalyticsController($container);
    return $controller->getSimilarClients($request, $response, $args);
});
