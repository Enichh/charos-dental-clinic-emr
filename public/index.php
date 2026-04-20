<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Environment-based security configuration
$isLocalDev = ($_ENV['APP_ENV'] ?? 'local') === 'local' || $_SERVER['HTTP_HOST'] === 'localhost';

if (!$isLocalDev) {
    // Production security settings - MUST be before session_start()
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 900);

    // HTTPS enforcement in production only
    if ($_SERVER['HTTPS'] !== 'on') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }

    // HSTS header
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
} else {
    // Local dev - relaxed settings for testing
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 900);
}

session_start();

// Check session timeout (15 minutes for healthcare)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header('Location: /auth/login?timeout=true');
    exit;
}
$_SESSION['last_activity'] = time();

error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? false);

$container = require __DIR__ . '/../config/container.php';

$routes = require __DIR__ . '/../src/Presentation/Routes/api.php';

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip the base path from the request URI
$basePath = $_ENV['BASE_PATH'] ?? '';
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
    if ($requestUri === '') {
        $requestUri = '/';
    }
}

$routeKey = "$requestMethod $requestUri";

if (!isset($routes[$routeKey])) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found']);
    exit;
}

$route = $routes[$routeKey];

if (is_array($route) && isset($route['controller'])) {
    $controllerInfo = $route['controller'];
    $middlewares = $route['middleware'] ?? [];
} else {
    $controllerInfo = $route;
    $middlewares = [];
}

$controllerClass = $controllerInfo[0];
$controllerMethod = $controllerInfo[1];

$controllerInstance = $container->get($controllerClass);

foreach ($middlewares as $middlewareClass) {
    $middleware = $container->get($middlewareClass);
    $next = function () use (&$middlewares, &$middlewareClass, $controllerInstance, $controllerMethod) {
        $controllerInstance->$controllerMethod();
    };
    $middleware->handle($next);
}

if (empty($middlewares)) {
    $controllerInstance->$controllerMethod();
}
