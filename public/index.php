<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? false);

$container = require __DIR__ . '/../config/container.php';

$routes = require __DIR__ . '/../src/Presentation/Routes/api.php';

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
