<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CandidAnalytics\Controllers\WebhookController;

// Webhooks are NOT protected by JWT auth - they use signature validation instead

// Projects/Bookings webhook
$app->post('/api/webhooks/projects', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveProject($request, $response);
});

// Revenue/Payments webhook
$app->post('/api/webhooks/revenue', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveRevenue($request, $response);
});

// Inquiries/Leads webhook
$app->post('/api/webhooks/inquiries', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveInquiry($request, $response);
});

// Consultations/Appointments webhook
$app->post('/api/webhooks/consultations', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveConsultation($request, $response);
});

// Stripe payment webhook (handles payment_intent.succeeded)
$app->post('/api/webhooks/stripe/payment', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveStripePayment($request, $response);
});

// Stripe refund webhook (handles charge.refunded)
$app->post('/api/webhooks/stripe/refund', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveStripeRefund($request, $response);
});

// Delivery status update webhook
$app->post('/api/webhooks/deliveries', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveDeliveryUpdate($request, $response);
});

// Client review webhook
$app->post('/api/webhooks/reviews', function (Request $request, Response $response) use ($container) {
    $controller = new WebhookController($container);
    return $controller->receiveReview($request, $response);
});

// Test webhook endpoint (for debugging)
$app->post('/api/webhooks/test', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $body = (string) $request->getBody();
    $data = json_decode($body, true);

    $logger->info('Test webhook received', [
        'headers' => $request->getHeaders(),
        'data' => $data
    ]);

    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Test webhook received',
        'received_data' => $data
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});
// Deploy trigger: 1763870951
