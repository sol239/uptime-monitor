<?php
$checker = new MonitorChecker; // <-- Move this to the top to see echo output from constructor
header('Content-Type: application/json');
require_once 'MonitorChecker.php';

// Autoload controllers
spl_autoload_register(function ($class) {
    $path = __DIR__ . "/controllers/$class.php";
    if (file_exists($path)) {
        require $path;
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Only one route: GET /
if ($method === 'GET' && $uri === '/') {
    $controller = new MainController();
    $controller->index();
} else {
    http_response_code(404);
    echo json_encode(["error" => "Route not found"]);
}

// Monitor Checker Service
$checker->initializeMonitorChecks();
$checker->runChecks();

