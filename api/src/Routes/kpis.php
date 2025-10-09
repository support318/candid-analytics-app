<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\KpiController;

// Priority KPIs endpoint
$app->get('/api/v1/kpis/priority', function (Request $request, Response $response) use ($container) {
    $controller = new KpiController($container);
    return $controller->getPriorityKpis($request, $response);
});
