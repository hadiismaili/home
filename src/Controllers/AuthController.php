<?php

namespace App\Controllers;

use App\Models\User;

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function showRegistrationForm(): void {
        $error = $_GET['error'] ?? null;
        $message = $_GET['message'] ?? null;
        require_once __DIR__ . '/../Views/register.php';
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($username) || empty($email) || empty($password)) {
                header("Location: /register?error=" . urlencode("همه‌ی فیلدها الزامی هستند.")); exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: /register?error=" . urlencode("فرمت ایمیل نامعتبر است.")); exit;
            }
            if ($password !== $confirm_password) {
                header("Location: /register?error=" . urlencode("رمزهای عبور مطابقت ندارند.")); exit;
            }
            if (strlen($password) < 6) {
                header("Location: /register?error=" . urlencode("رمز عبور باید حداقل ۶ کاراکتر باشد.")); exit;
            }
            if ($this->userModel->findByUsername($username)) {
                header("Location: /register?error=" . urlencode("این نام کاربری قبلا گرفته شده است.")); exit;
            }
            if ($this->userModel->findByEmail($email)) {
                header("Location: /register?error=" . urlencode("این ایمیل قبلا ثبت شده است.")); exit;
            }

            // Regular users are not created as admin by default
            if ($this->userModel->create($username, $email, $password, false)) { // Explicitly false for isAdmin
                header("Location: /login?message=" . urlencode("ثبت نام موفقیت آمیز بود. لطفا وارد شوید.")); exit;
            } else {
                header("Location: /register?error=" . urlencode("خطا در ثبت نام. لطفا دوباره تلاش کنید.")); exit;
            }
        } else {
            $this->showRegistrationForm();
        }
    }

    public function showLoginForm(): void {
        $error = $_GET['error'] ?? null;
        $message = $_GET['message'] ?? null;
        require_once __DIR__ . '/../Views/login.php';
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                header("Location: /login?error=" . urlencode("نام کاربری و رمز عبور الزامی هستند.")); exit;
            }
            $user = $this->userModel->findByUsername($username);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username']; // Keep username for general use

                if ((bool)$user['is_admin']) {
                    header("Location: /admin/dashboard");
                } else {
                    header("Location: /leitner/dashboard");
                }
                exit;
            } else {
                header("Location: /login?error=" . urlencode("نام کاربری یا رمز عبور نامعتبر است.")); exit;
            }
        } else {
            $this->showLoginForm();
        }
    }

    public function logout(): void {
        session_unset(); // Unset all session variables
        session_destroy(); // Destroy the session
        header("Location: /login?message=" . urlencode("شما با موفقیت خارج شدید."));
        exit;
    }

    // This showHome is called by the /home route.
    // Login will redirect to either /admin/dashboard or /leitner/dashboard directly.
    // This method can serve as a fallback or if /home is accessed directly by a logged-in user.
    public function showHome(): void {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login"); exit;
        }

        $currentUser = $this->userModel->findById($_SESSION['user_id']);
        if ($currentUser && (bool)$currentUser['is_admin']) {
             header("Location: /admin/dashboard");
        } else {
             header("Location: /leitner/dashboard");
        }
        exit;
    }
}
