<?php
namespace App\Controllers;

use App\Models\LearningSet;
use App\Models\UserProgressService;
use App\Models\User;

class LeitnerController {
    private UserProgressService $userProgressService;
    private LearningSet $learningSetModel;
    private User $userModel;
    private ?int $currentUserId;
    private ?string $currentUsername;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /login?error=" . urlencode("لطفا برای دسترسی وارد شوید."));
            exit;
        }
        $this->currentUserId = (int)$_SESSION['user_id'];
        $this->currentUsername = $_SESSION['username'] ?? 'کاربر';

        $this->userProgressService = new UserProgressService();
        $this->learningSetModel = new LearningSet();
        $this->userModel = new User();
    }

    public function showDashboard(): void {
        $activeLearningSetId = $this->userModel->getActiveLearningSetId($this->currentUserId);
        $activeLearningSet = null;
        $stats = null;
        $isReviewAvailable = false;

        // Preserve warning from previous operations if any, then clear it from session
        $flash_warning = $_SESSION['flash_warning'] ?? null;
        unset($_SESSION['flash_warning']);

        if ($activeLearningSetId) {
            $activeLearningSet = $this->learningSetModel->findById($activeLearningSetId);
            if ($activeLearningSet) {
                 $stats = $this->userProgressService->getCardStatsForSet($this->currentUserId, $activeLearningSetId);
                 $isReviewAvailable = ($stats['due'] > 0);
            } else {
                $this->userModel->setActiveLearningSet($this->currentUserId, null);
                $activeLearningSetId = null;
                // Override any existing warning with this more specific one if set was invalid
                $flash_warning = "مجموعه آموزشی فعال قبلی شما دیگر موجود نیست. لطفا یک مجموعه جدید انتخاب کنید.";
            }
        }

        $availableSets = $this->learningSetModel->getAll('name', 'ASC');

        $message = $_SESSION['flash_message'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_message'], $_SESSION['flash_error']); // Clear after fetching

        // Make $flash_warning available to the view
        $warning = $flash_warning;

        require_once __DIR__ . '/../Views/leitner/dashboard.php';
    }

    public function activateLearningSet(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = "متد نامعتبر.";
            header("Location: /leitner/dashboard");
            exit;
        }
        $newSetIdInput = $_POST['learning_set_id'] ?? ''; // Get as string first

        // Handle case where user deselects (chooses empty option which results in empty string)
        if ($newSetIdInput === '') {
            $this->userModel->setActiveLearningSet($this->currentUserId, null);
            $_SESSION['flash_message'] = "هیچ مجموعه آموزشی فعالی انتخاب نشده است.";
            header("Location: /leitner/dashboard"); exit;
        }

        $newSetId = filter_var($newSetIdInput, FILTER_VALIDATE_INT);
        if ($newSetId === false || $newSetId <= 0) { // Ensure positive integer
            $_SESSION['flash_error'] = "مجموعه آموزشی انتخاب شده نامعتبر است.";
            header("Location: /leitner/dashboard"); exit;
        }

        $setExists = $this->learningSetModel->findById($newSetId);
        if (!$setExists) {
            $_SESSION['flash_error'] = "مجموعه آموزشی انتخاب شده وجود ندارد.";
            header("Location: /leitner/dashboard"); exit;
        }

        $currentActiveSetId = $this->userModel->getActiveLearningSetId($this->currentUserId);
        $pdo = $this->userModel->getDbConnection();
        try {
            $pdo->beginTransaction();
            $updateResult = $this->userModel->setActiveLearningSet($this->currentUserId, $newSetId);

            if (!$updateResult && $currentActiveSetId !== $newSetId) {
                // If rowCount is 0 but it wasn't already the active set, it's an issue.
                throw new \Exception("خطا در بروزرسانی مجموعه فعال کاربر.");
            }
            $pdo->commit();
            $_SESSION['flash_message'] = "مجموعه آموزشی '" . htmlspecialchars($setExists['name']) . "' با موفقیت فعال شد.";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error'] = "خطا در فعال سازی مجموعه: " . $e->getMessage();
        }
        header("Location: /leitner/dashboard");
        exit;
    }

    public function showReview(): void {
        $activeLearningSetId = $this->userModel->getActiveLearningSetId($this->currentUserId);
        if (!$activeLearningSetId) {
            $_SESSION['flash_warning'] = "ابتدا یک مجموعه آموزشی را برای مرور انتخاب کنید.";
            header("Location: /leitner/dashboard");
            exit;
        }
        $dueCards = $this->userProgressService->getDueCardsForSet($this->currentUserId, $activeLearningSetId, 1);

        if (empty($dueCards)) {
            $_SESSION['flash_message'] = "در حال حاضر کارتی برای مرور در این مجموعه آماده نیست! می‌توانید مجموعه دیگری را انتخاب کنید یا بعدا مراجعه کنید.";
            header("Location: /leitner/dashboard");
            exit;
        }
        $currentCard = $dueCards[0];

        $message = $_SESSION['flash_message'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_message'], $_SESSION['flash_error']);

        require_once __DIR__ . '/../Views/leitner/review.php';
    }

    public function processReviewOutcome(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = "متد درخواست برای پردازش مرور نامعتبر است.";
            header("Location: /leitner/dashboard");
            exit;
        }
        $progressId = filter_input(INPUT_POST, 'card_id', FILTER_VALIDATE_INT);
        $outcome = $_POST['outcome'] ?? '';

        if (!$progressId || $progressId === false ||
            !in_array($outcome, [
                UserProgressService::OUTCOME_CORRECT,
                UserProgressService::OUTCOME_INCORRECT,
                UserProgressService::OUTCOME_PARTIAL
            ])) {
            $_SESSION['flash_error'] = "اطلاعات مرور ارسال شده نامعتبر است.";
            header("Location: /leitner/review");
            exit;
        }

        if ($this->userProgressService->processReview($progressId, $this->currentUserId, $outcome)) {
            $message = "کارت بروز شد.";
            switch($outcome) {
                case UserProgressService::OUTCOME_CORRECT: $message = "کارت بروز شد: پاسخ صحیح ثبت شد."; break;
                case UserProgressService::OUTCOME_PARTIAL: $message = "کارت بروز شد: پاسخ نسبی ثبت شد."; break;
                case UserProgressService::OUTCOME_INCORRECT: $message = "کارت بروز شد: پاسخ نادرست ثبت شد."; break;
            }
            $_SESSION['flash_message'] = $message;
        } else {
            $_SESSION['flash_error'] = "خطا در پردازش مرور برای کارت. ممکن است کارت متعلق به شما نباشد یا خطای دیگری رخ داده است.";
        }
        header("Location: /leitner/review");
        exit;
    }
}
