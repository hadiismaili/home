<?php
namespace App\Controllers\Admin;
use App\Models\User;

abstract class BaseAdminController {
    protected ?int \$currentUserId;
    protected User \$userModel;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset(\$_SESSION['user_id'])) {
            header("Location: /login?error=" . urlencode("لطفا برای دسترسی به پنل ادمین وارد شوید."));
            exit;
        }
        \$this->currentUserId = \$_SESSION['user_id'];
        \$this->userModel = new User();
        if (!\$this->userModel->isAdmin(\$this->currentUserId)) {
            header("Location: /leitner/dashboard?error=" . urlencode("شما دسترسی ادمین ندارید."));
            exit;
        }
         // Pass username to admin views via session
        if(isset(\$_SESSION['username'])) {
            \$_SESSION['admin_username'] = \$_SESSION['username'];
        } else {
            \$currentUserData = \$this->userModel->findById(\$this->currentUserId);
            \$_SESSION['admin_username'] = \$currentUserData ? \$currentUserData['username'] : 'Admin';
        }
    }
}
