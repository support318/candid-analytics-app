<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

// Autoload dependencies
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// =============================================================================
// Container Definitions
// =============================================================================

// Database
$container->set('db', function() {
    return new \CandidAnalytics\Services\Database(
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );
});

// Redis Cache
$container->set('redis', function() {
    return new \Predis\Client([
        'scheme' => 'tcp',
        'host' => $_ENV['REDIS_HOST'],
        'port' => (int)$_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'] ?: null,
    ]);
});

// Logger
$container->set('logger', function() {
    $logger = new \Monolog\Logger('candid-analytics');
    $logger->pushHandler(
        new \Monolog\Handler\StreamHandler(
            $_ENV['LOG_PATH'] ?? __DIR__ . '/../logs/app.log',
            \Monolog\Logger::toMonologLevel($_ENV['LOG_LEVEL'] ?? 'info')
        )
    );
    return $logger;
});

// =============================================================================
// CORS Middleware (handles both preflight and actual requests)
// =============================================================================

$app->add(function (Request $request, $handler) use ($app) {
    $origin = $request->getHeaderLine('Origin');
    $allowedOrigins = explode(',', $_ENV['ALLOWED_ORIGINS'] ?? '');

    // Check if origin is allowed
    $isAllowedOrigin = in_array($origin, $allowedOrigins);

    // Handle OPTIONS preflight request immediately
    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();

        if ($isAllowedOrigin) {
            return $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', $_ENV['CORS_MAX_AGE'] ?? '86400')
                ->withStatus(200);
        }

        return $response->withStatus(403);
    }

    // Handle actual request
    $response = $handler->handle($request);

    // Add CORS headers to response
    if ($isAllowedOrigin) {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
});

// =============================================================================
// JWT Authentication Middleware
// =============================================================================

$jwtMiddleware = new \Tuupola\Middleware\JwtAuthentication([
    "secret" => $_ENV['JWT_SECRET'],
    "algorithm" => [$_ENV['JWT_ALGORITHM'] ?? 'HS256'],
    "path" => "/api",
    "ignore" => ["/api/auth/login", "/api/auth/refresh", "/api/health"],
    "error" => function ($response, $arguments) {
        $data = [
            "success" => false,
            "error" => [
                "code" => "UNAUTHORIZED",
                "message" => $arguments["message"]
            ]
        ];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(401)
            ->getBody()->write(json_encode($data));
    },
    "before" => function ($request, $arguments) use ($container) {
        // Add user info to request attributes
        $container->set('jwt', $arguments["decoded"]);
    }
]);

$app->add($jwtMiddleware);

// =============================================================================
// API Routes
// =============================================================================

// Health Check
$app->get('/api/health', function (Request $request, Response $response) {
    $data = [
        "success" => true,
        "data" => [
            "status" => "healthy",
            "version" => $_ENV['APP_VERSION'] ?? '1.0.0',
            "timestamp" => date('c')
        ]
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Authentication Routes
require __DIR__ . '/../src/Routes/auth.php';

// User Management Routes
require __DIR__ . '/../src/Routes/users.php';

// KPI Routes
require __DIR__ . '/../src/Routes/kpis.php';
require __DIR__ . '/../src/Routes/revenue.php';
require __DIR__ . '/../src/Routes/sales.php';
require __DIR__ . '/../src/Routes/operations.php';
require __DIR__ . '/../src/Routes/satisfaction.php';
require __DIR__ . '/../src/Routes/marketing.php';
require __DIR__ . '/../src/Routes/staff.php';
require __DIR__ . '/../src/Routes/ai.php';

// Webhook Routes
require __DIR__ . '/../src/Routes/webhooks.php';

// =============================================================================
// Error Handling
// =============================================================================

$errorMiddleware = $app->addErrorMiddleware(
    (bool)$_ENV['APP_DEBUG'],
    true,
    true
);

$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function (Request $request, Throwable $exception) use ($app) {
        $response = $app->getResponseFactory()->createResponse();
        $data = [
            "success" => false,
            "error" => [
                "code" => "NOT_FOUND",
                "message" => "Endpoint not found",
                "path" => $request->getUri()->getPath()
            ]
        ];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }
);

// Run app
$app->run();
