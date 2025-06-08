<?php

namespace App\Controllers;

use App\Models\Word;
use App\Models\LeitnerCard;

class LeitnerController {
    private Word $wordModel;
    private LeitnerCard $leitnerCardModel;
    private ?int $currentUserId;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /login?error=" . urlencode("لطفا برای دسترسی به جعبه لایتنر وارد شوید."));
            exit;
        }
        $this->currentUserId = $_SESSION['user_id'];
        $this->wordModel = new Word();
        $this->leitnerCardModel = new LeitnerCard();
    }

    // Audio file system helper methods (handleAudioUpload, deleteAudioFile) are removed.

    public function showDashboard(): void {
        $stats = $this->leitnerCardModel->getCardStats($this->currentUserId);
        $username = $_SESSION['username'] ?? 'کاربر';
        $isReviewAvailable = ($stats['due'] > 0);
        require_once __DIR__ . '/../Views/leitner/dashboard.php';
    }

    public function showAddWordForm(): void {
        $error = $_GET['error'] ?? null;
        $message = $_GET['message'] ?? null;
        require_once __DIR__ . '/../Views/leitner/add_word.php';
    }

    public function addWord(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $germanWord = trim($_POST['german_word'] ?? '');
            $translation = trim($_POST['translation'] ?? '');
            $persianPhonetic = trim($_POST['persian_phonetic_pronunciation'] ?? '') ?: null;
            $wordTypeGender = trim($_POST['word_type_and_gender'] ?? '') ?: null;
            $wordLevel = trim($_POST['word_level'] ?? '') ?: null;
            $exampleGerman = trim($_POST['example_german'] ?? '') ?: null;
            $examplePersian = trim($_POST['example_persian_translation'] ?? '') ?: null;
            $audioUrl = trim($_POST['audio_url'] ?? '') ?: null;


            if (empty($germanWord) || empty($translation)) {
                header("Location: /leitner/add?error=" . urlencode("کلمه آلمانی و ترجمه آن الزامی است."));
                exit;
            }
            if ($this->wordModel->findByGermanWord($germanWord, $this->currentUserId)) {
                header("Location: /leitner/add?error=" . urlencode("این کلمه از قبل در واژگان شما موجود است."));
                exit;
            }

            $pdo = $this->wordModel->getDbConnection(); // Assuming this method exists in Word model
            try {
                $pdo->beginTransaction();

                $wordId = $this->wordModel->create(
                    $this->currentUserId, $germanWord, $translation,
                    $persianPhonetic, $wordTypeGender, $wordLevel,
                    $exampleGerman, $examplePersian, $audioUrl
                );

                if ($wordId) {
                    if ($this->leitnerCardModel->create($wordId, $this->currentUserId)) {
                        $pdo->commit();
                        header("Location: /leitner/add?message=" . urlencode("کلمه با موفقیت اضافه شد و در جعبه آشنایی قرار گرفت."));
                        exit;
                    } else {
                        $pdo->rollBack();
                        header("Location: /leitner/add?error=" . urlencode("خطا در ایجاد کارت لایتنر."));
                        exit;
                    }
                } else {
                    $pdo->rollBack();
                    header("Location: /leitner/add?error=" . urlencode("خطا در افزودن کلمه."));
                    exit;
                }
            } catch (\Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Error in LeitnerController::addWord: " . $e->getMessage());
                header("Location: /leitner/add?error=" . urlencode("یک خطای سیستمی هنگام افزودن کلمه رخ داد. لطفا دوباره تلاش کنید."));
                exit;
            }
        }
        $this->showAddWordForm();
    }

    public function updateWord(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /leitner/vocabulary?error=" . urlencode("متد نامعتبر."));
            exit;
        }

        $wordId = filter_input(INPUT_POST, 'word_id', FILTER_VALIDATE_INT);
        $germanWord = trim($_POST['german_word'] ?? '');
        $translation = trim($_POST['translation'] ?? '');
        $persianPhonetic = trim($_POST['persian_phonetic_pronunciation'] ?? '') ?: null;
        $wordTypeGender = trim($_POST['word_type_and_gender'] ?? '') ?: null;
        $wordLevel = trim($_POST['word_level'] ?? '') ?: null;
        $exampleGerman = trim($_POST['example_german'] ?? '') ?: null;
        $examplePersian = trim($_POST['example_persian_translation'] ?? '') ?: null;
        $audioUrl = trim($_POST['audio_url'] ?? '') ?: null;

        if (!$wordId || $wordId === false || empty($germanWord) || empty($translation)) {
            // If wordId is available, redirect back to edit page, otherwise to vocabulary list
            $redirectUrl = $wordId ? "/leitner/edit?id={$wordId}&error=" : "/leitner/vocabulary?error=";
            header("Location: " . $redirectUrl . urlencode("کلمه آلمانی و ترجمه الزامی هستند و شناسه کلمه باید معتبر باشد."));
            exit;
        }

        $existingWord = $this->wordModel->findById($wordId, $this->currentUserId);
        if (!$existingWord) {
            header("Location: /leitner/vocabulary?error=" . urlencode("کلمه یافت نشد یا دسترسی مجاز نیست."));
            exit;
        }

        $conflictWord = $this->wordModel->findByGermanWord($germanWord, $this->currentUserId);
        if ($conflictWord && $conflictWord['id'] !== $wordId) {
            header("Location: /leitner/edit?id=$wordId&error=" . urlencode("کلمه دیگری با این عبارت آلمانی موجود است."));
            exit;
        }

        try {
            if ($this->wordModel->update(
                $wordId, $this->currentUserId, $germanWord, $translation,
                $persianPhonetic, $wordTypeGender, $wordLevel,
                $exampleGerman, $examplePersian, $audioUrl
            )) {
                header("Location: /leitner/vocabulary?message=" . urlencode("کلمه با موفقیت بروزرسانی شد."));
                exit;
            } else {
                // This might mean no actual data changed, or an update error not throwing PDOException
                header("Location: /leitner/edit?id=$wordId&error=" . urlencode("خطا در بروزرسانی کلمه یا تغییری ایجاد نشد."));
                exit;
            }
        } catch (\Exception $e) {
            error_log("Error in LeitnerController::updateWord: " . $e->getMessage());
            header("Location: /leitner/edit?id=$wordId&error=" . urlencode("یک خطای سیستمی هنگام بروزرسانی کلمه رخ داد. لطفا دوباره تلاش کنید."));
            exit;
        }
    }

    public function deleteWord(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /leitner/vocabulary?error=" . urlencode("متد نامعتبر برای حذف."));
            exit;
        }
        $wordId = filter_input(INPUT_POST, 'word_id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            header("Location: /leitner/vocabulary?error=" . urlencode("شناسه کلمه نامعتبر برای حذف."));
            exit;
        }

        $word = $this->wordModel->findById($wordId, $this->currentUserId);
        if (!$word) {
            header("Location: /leitner/vocabulary?error=" . urlencode("کلمه برای حذف یافت نشد یا دسترسی شما مجاز نیست."));
            exit;
        }

        // Since audio_url is just a URL, no server-side file deletion is handled here anymore.
        // If self-hosted audio URLs were used and files were on this server,
        // further logic might be needed if those files should be deleted.
        // For external URLs, this is sufficient.
        if ($this->wordModel->delete($wordId, $this->currentUserId)) {
            header("Location: /leitner/vocabulary?message=" . urlencode("کلمه با موفقیت حذف شد."));
            exit;
        } else {
            header("Location: /leitner/vocabulary?error=" . urlencode("خطا در حذف کلمه."));
            exit;
        }
    }

    public function showReview(): void {
        $dueCards = $this->leitnerCardModel->getDueCards($this->currentUserId, 1);
        if (empty($dueCards)) {
            header("Location: /leitner/dashboard?message=" . urlencode("در حال حاضر کارتی برای مرور آماده نیست!"));
            exit;
        }
        $currentCard = $dueCards[0];
        $error = $_GET['error'] ?? null;
        $message = $_GET['message'] ?? null;
        require_once __DIR__ . '/../Views/leitner/review.php';
    }

    public function processReviewOutcome(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /leitner/review?error=" . urlencode("متد نامعتبر."));
            exit;
        }
        $cardId = filter_input(INPUT_POST, 'card_id', FILTER_VALIDATE_INT);
        $outcome = $_POST['outcome'] ?? '';

        if (!$cardId || $cardId === false ||
            !in_array($outcome, [
                LeitnerCard::OUTCOME_CORRECT,
                LeitnerCard::OUTCOME_INCORRECT,
                LeitnerCard::OUTCOME_PARTIAL
            ])) {
            header("Location: /leitner/review?error=" . urlencode("اطلاعات مرور ارسال شده نامعتبر است."));
            exit;
        }

        if ($this->leitnerCardModel->processReview($cardId, $this->currentUserId, $outcome)) {
            $message = "کارت بروز شد.";
            switch($outcome) {
                case LeitnerCard::OUTCOME_CORRECT: $message = "کارت بروز شد: پاسخ صحیح ثبت شد."; break;
                case LeitnerCard::OUTCOME_PARTIAL: $message = "کارت بروز شد: پاسخ نسبی ثبت شد."; break;
                case LeitnerCard::OUTCOME_INCORRECT: $message = "کارت بروز شد: پاسخ نادرست ثبت شد."; break;
            }
            header("Location: /leitner/review?message=" . urlencode($message));
        } else {
            header("Location: /leitner/review?error=" . urlencode("خطا در پردازش مرور. لطفا دوباره تلاش کنید."));
        }
        exit;
    }

    public function showVocabularyList(): void {
        $words = $this->wordModel->getAllByUser($this->currentUserId);
        $message = $_GET['message'] ?? null;
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../Views/leitner/list_vocabulary.php';
    }

    public function showEditWordForm(): void {
        $wordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            header("Location: /leitner/vocabulary?error=" . urlencode("شناسه کلمه نامعتبر است."));
            exit;
        }
        $word = $this->wordModel->findById($wordId, $this->currentUserId);
        if (!$word) {
            header("Location: /leitner/vocabulary?error=" . urlencode("کلمه یافت نشد یا دسترسی شما مجاز نیست."));
            exit;
        }
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../Views/leitner/edit_word.php';
    }
}
