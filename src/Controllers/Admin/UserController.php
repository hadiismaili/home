<?php
namespace App\Controllers\Admin;

use App\Models\User; // Already available via $this->userModel from BaseAdminController
use App\Models\Word;
use App\Models\LeitnerCard;

class UserController extends BaseAdminController {
    private Word $wordModelInstance; // Specific instance for this controller's needs
    private LeitnerCard $leitnerCardModelInstance; // Specific instance for this controller's needs

    public function __construct() {
        parent::__construct(); // Ensures admin check and sets up $this->userModel from Base
        $this->wordModelInstance = new Word();
        $this->leitnerCardModelInstance = new LeitnerCard();
    }

    public function listUsers(): void {
        $rawUsers = $this->userModel->getAllUsers(); // $this->userModel is from BaseAdminController
        $usersWithStats = [];
        foreach ($rawUsers as $user) {
            $user['word_count'] = $this->wordModelInstance->countWordsByUserId((int)$user['id']);
            $user['card_count'] = $this->leitnerCardModelInstance->countCardsByUserId((int)$user['id']);
            $user['due_cards_count'] = $this->leitnerCardModelInstance->countDueCardsByUserId((int)$user['id']);
            $usersWithStats[] = $user;
        }

        $users = $usersWithStats; // Use the enriched array
        $message = $_GET['message'] ?? null;
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../../Views/admin/users/list.php';
    }

    public function toggleAdminStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/users?error=" . urlencode("متد نامعتبر.")); exit;
        }
        $userIdToToggle = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!isset($userIdToToggle) || $userIdToToggle === false) {
            header("Location: /admin/users?error=" . urlencode("شناسه کاربر نامعتبر است.")); exit;
        }

        // $this->currentUserId is from BaseAdminController (int)
        // $userIdToToggle is from filter_input (int|false|null)
        if ($userIdToToggle === $this->currentUserId) {
            $currentUserData = $this->userModel->findById($this->currentUserId);
            if ($currentUserData && (bool)$currentUserData['is_admin']) {
                $allUsers = $this->userModel->getAllUsers(); // Potentially re-fetch to be sure
                $adminCount = 0;
                foreach ($allUsers as $u) { if ((bool)$u['is_admin']) $adminCount++; }
                if ($adminCount <= 1) {
                    header("Location: /admin/users?error=" . urlencode("شما نمی‌توانید وضعیت ادمین تنها مدیر سیستم را لغو کنید.")); exit;
                }
            }
        }

        $user = $this->userModel->findById($userIdToToggle);
        if (!$user) {
            header("Location: /admin/users?error=" . urlencode("کاربر یافت نشد.")); exit;
        }
        $newStatus = !(bool)$user['is_admin'];
        if ($this->userModel->setAdminStatus($userIdToToggle, $newStatus)) {
            header("Location: /admin/users?message=" . urlencode("وضعیت ادمین کاربر " . htmlspecialchars($user['username']) . " با موفقیت تغییر کرد."));
        } else {
            header("Location: /admin/users?error=" . urlencode("خطا در تغییر وضعیت ادمین کاربر."));
        }
        exit;
    }

    public function deleteUser(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/users?error=" . urlencode("متد نامعتبر برای حذف.")); exit;
        }
        $userIdToDelete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!isset($userIdToDelete) || $userIdToDelete === false) {
            header("Location: /admin/users?error=" . urlencode("شناسه کاربر نامعتبر برای حذف.")); exit;
        }
        if ($userIdToDelete === $this->currentUserId) {
            header("Location: /admin/users?error=" . urlencode("شما نمی‌توانید حساب کاربری خود را از این طریق حذف کنید.")); exit;
        }
        $user = $this->userModel->findById($userIdToDelete);
        if (!$user) { // Corrected variable name from prompt
            header("Location: /admin/users?error=" . urlencode("کاربر برای حذف یافت نشد.")); exit;
        }
        if ($this->userModel->deleteById($userIdToDelete)) {
            header("Location: /admin/users?message=" . urlencode("کاربر " . htmlspecialchars($user['username']) . " با موفقیت حذف شد."));
        } else {
            header("Location: /admin/users?error=" . urlencode("خطا در حذف کاربر."));
        }
        exit;
    }
}
