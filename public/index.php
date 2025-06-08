<?php

// Simple Autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to path: App\Core\Database -> src/Core/Database.php
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Not an App class
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        // Log or die if class file not found for debugging
        // die("File not found for class: " . $file);
    }
});

use App\Controllers\AuthController;
use App\Core\Database; // For initializing DB
// LeitnerController will be autoloaded when needed by routes

// Initialize database connection and ensure tables are created
$db = new Database();
$db->getConnection(); // This will call initDatabase if not already initialized

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/'; // Default to / if not set (CLI)
$base_path = ''; // If your app is in a subdirectory, set it here e.g., /myapp

// Remove base_path from URI if necessary
if (!empty($base_path) && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}
// If base_path is empty, ensure request_uri starts with /
if (empty($base_path) && ($request_uri === '' || $request_uri[0] !== '/')) {
    $request_uri = '/' . $request_uri;
}


// Remove query string from URI for routing
$route = strtok($request_uri, '?');
if ($route === false) $route = '/'; // Handle case where URI is just '/'

switch ($route) {
    case '/':
    case '/home':
        $authController = new AuthController();
        $authController->showHome(); // This will now redirect to /leitner/dashboard
        break;
    case '/register':
        $authController = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } else {
            $authController->showRegistrationForm();
        }
        break;
    case '/login':
        $authController = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            $authController->showLoginForm();
        }
        break;
    case '/logout':
        $authController = new AuthController();
        $authController->logout();
        break;
    case '/leitner/dashboard':
        $leitnerController = new \App\Controllers\LeitnerController();
        $leitnerController->showDashboard();
        break;
    case '/leitner/add':
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->addWord();
        } else {
            $leitnerController->showAddWordForm();
        }
        break;
    case '/leitner/review':
        $leitnerController = new \App\Controllers\LeitnerController();
        $leitnerController->showReview();
        break;
    case '/leitner/review/process':
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->processReviewOutcome();
        } else {
            header("Location: /leitner/review?error=Invalid+access+method");
            exit;
        }
        break;
    case '/leitner/vocabulary':
        $leitnerController = new \App\Controllers\LeitnerController();
        $leitnerController->showVocabularyList();
        break;
    case '/leitner/edit': // GET for form, POST for update
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->updateWord();
        } else {
            $leitnerController->showEditWordForm(); // Expects 'id' in query string
        }
        break;
    case '/leitner/delete': // POST for delete
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->deleteWord();
        } else {
            header("Location: /leitner/vocabulary?error=Invalid+access+method+for+delete");
            exit;
        }
        break;
    default:
        http_response_code(404);
        echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><title>404 - صفحه یافت نشد</title><link rel='stylesheet' href='/css/style.css'></head><body>";
        echo "<header><h1>خطای 404</h1></header><main>";
        echo "<h1>404 - صفحه مورد نظر یافت نشد</h1><p><a href='/leitner/dashboard'>بازگشت به داشبورد</a></p>";
        echo "</main><footer><p>&copy; " . date("Y") . " جعبه لایتنر</p></footer></body></html>";
        break;
}
