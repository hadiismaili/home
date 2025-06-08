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
    case '/leitner/activate-set': // POST only
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController = new \App\Controllers\LeitnerController();
            $leitnerController->activateLearningSet();
        } else {
            $_SESSION['flash_error'] = "درخواست نامعتبر.";
            header("Location: /leitner/dashboard");
            exit;
        }
        break;
    // Old Leitner word management routes are removed as per controller changes.
    // case '/leitner/add': ...
    // case '/leitner/vocabulary': ...
    // case '/leitner/edit': ...
    // case '/leitner/delete': ...
    case '/leitner/review':
        $leitnerController = new \App\Controllers\LeitnerController();
        $leitnerController->showReview();
        break;
    case '/leitner/review/process':
        $leitnerController = new \App\Controllers\LeitnerController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leitnerController->processReviewOutcome();
        } else {
            $_SESSION['flash_error'] = "متد دسترسی نامعتبر برای پردازش مرور."; // Changed from $_SESSION['error']
            header("Location: /leitner/review");
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
            $_SESSION['flash_error'] = "درخواست نامعتبر.";
            header("Location: /admin/users"); exit;
        }
        break;
    case '/admin/users/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminUserController = new \App\Controllers\Admin\UserController();
            $adminUserController->deleteUser();
        } else {
            $_SESSION['flash_error'] = "درخواست نامعتبر.";
            header("Location: /admin/users"); exit;
        }
        break;
    case '/admin/global-words':
        $globalWordController = new \App\Controllers\Admin\GlobalWordController();
        $globalWordController->listWords();
        break;
    case '/admin/global-words/add':
        $globalWordController = new \App\Controllers\Admin\GlobalWordController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $globalWordController->addWord();
        } else {
            $globalWordController->showAddForm();
        }
        break;
    case '/admin/global-words/edit':
        $globalWordController = new \App\Controllers\Admin\GlobalWordController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $globalWordController->updateWord();
        } else {
            $globalWordController->showEditForm();
        }
        break;
    case '/admin/global-words/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $globalWordController = new \App\Controllers\Admin\GlobalWordController();
            $globalWordController->deleteWord();
        } else {
            $_SESSION['flash_error'] = "متد نامعتبر برای حذف کلمه از بانک جهانی.";
            header("Location: /admin/global-words");
            exit;
        }
        break;
    case '/admin/learning-sets':
        $learningSetController = new \App\Controllers\Admin\LearningSetController();
        $learningSetController->listSets();
        break;
    case '/admin/learning-sets/add':
        $learningSetController = new \App\Controllers\Admin\LearningSetController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $learningSetController->addSet();
        } else {
            $learningSetController->showAddForm();
        }
        break;
    case '/admin/learning-sets/edit':
        $learningSetController = new \App\Controllers\Admin\LearningSetController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $learningSetController->updateSet();
        } else {
            $learningSetController->showEditForm();
        }
        break;
    case '/admin/learning-sets/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $learningSetController = new \App\Controllers\Admin\LearningSetController();
            $learningSetController->deleteSet();
        } else {
            $_SESSION['flash_error'] = "متد نامعتبر برای حذف مجموعه آموزشی.";
            header("Location: /admin/learning-sets");
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
