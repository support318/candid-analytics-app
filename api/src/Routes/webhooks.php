<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Webhook receiver for Make.com scenarios
// These endpoints integrate with your existing Make.com scenarios

$app->post('/api/webhooks/lead-capture', function (Request $request, Response $response) use ($container) {
    $data = $request->getParsedBody();
    $db = $container->get('db');
    $logger = $container->get('logger');

    // Log webhook received
    $logger->info('Lead capture webhook received', ['data' => $data]);

    // Your Make.com scenario will insert data directly to PostgreSQL
    // This endpoint is for logging/monitoring purposes

    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Webhook received'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/webhooks/project-booked', function (Request $request, Response $response) use ($container) {
    $data = $request->getParsedBody();
    $logger = $container->get('logger');

    $logger->info('Project booked webhook received', ['data' => $data]);

    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Webhook received'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

// Add more webhook endpoints as needed
