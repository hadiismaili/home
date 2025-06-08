<?php
namespace App\Controllers\Admin;

use App\Models\User;
use App\Models\Word;
use App\Models\LeitnerCard;

class DashboardController extends BaseAdminController {

    private User $userModelInstance;
    private Word $wordModelInstance;
    private LeitnerCard $leitnerCardModelInstance;

    public function __construct() {
        parent::__construct();
        $this->userModelInstance = new User();
        $this->wordModelInstance = new Word();
        $this->leitnerCardModelInstance = new LeitnerCard();
    }

    public function showDashboard(): void {
        $adminUsername = $_SESSION['admin_username'] ?? ($_SESSION['username'] ?? 'Admin');

        $totalUsers = $this->userModelInstance->countAll();
        $totalWords = $this->wordModelInstance->countAll();
        $avgWordsPerUser = ($totalUsers > 0) ? round($totalWords / $totalUsers, 2) : 0;

        $stats = [
            'total_users' => $totalUsers,
            'total_words' => $totalWords,
            'avg_words_per_user' => $avgWordsPerUser,
            'total_cards' => $this->leitnerCardModelInstance->countAll(),
            'total_due_today' => $this->leitnerCardModelInstance->countAllDueToday(), // System-wide due
            'box_distribution' => $this->leitnerCardModelInstance->getSystemWideBoxDistribution()
        ];

        // Pass $adminUsername and $stats to the view
        require_once __DIR__ . '/../../Views/admin/dashboard.php';
    }
}
