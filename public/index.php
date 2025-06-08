<?php

// Simple Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\AuthController;
use App\Core\Database;

$db = new Database();
$db->getConnection();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$base_path = '';

if (!empty($base_path) && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}
if (empty($base_path) && ($request_uri === '' || $request_uri[0] !== '/')) {
    $request_uri = '/' . $request_uri;
}

$route = strtok($request_uri, '?');
if ($route === false) $route = '/';

switch ($route) {
    case '/':
    case '/home':
        $authController = new AuthController();
        $authController->showHome();
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
    // Leitner Routes
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
    case '/leitner/edit':
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->updateWord();
        } else {
            $leitnerController->showEditWordForm();
        }
        break;
    case '/leitner/delete':
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->deleteWord();
        } else {
            header("Location: /leitner/vocabulary?error=Invalid+access+method+for+delete");
            exit;
        }
        break;
    // Admin Routes
    case '/admin':
    case '/admin/dashboard':
        $adminDashboardController = new \App\Controllers\Admin\DashboardController();
        $adminDashboardController->showDashboard();
        break;
    case '/admin/users':
        $adminUserController = new \App\Controllers\Admin\UserController();
        $adminUserController->listUsers();
        break;
    case '/admin/users/toggle-admin':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminUserController = new \App\Controllers\Admin\UserController();
            $adminUserController->toggleAdminStatus();
        } else {
            header("Location: /admin/users?error=Invalid+request"); exit;
        }
        break;
    case '/admin/users/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminUserController = new \App\Controllers\Admin\UserController();
            $adminUserController->deleteUser();
        } else {
            header("Location: /admin/users?error=Invalid+request"); exit;
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
