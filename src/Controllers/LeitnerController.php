<?php

namespace App\Controllers;

use App\Models\Word;
use App\Models\LeitnerCard; // This use statement allows using LeitnerCard::OUTCOME_CORRECT etc.

class LeitnerController {
    private Word $wordModel;
    private LeitnerCard $leitnerCardModel;
    private ?int $currentUserId;

    private function handleAudioUpload(array $fileInfo, ?string $existingFilename = null): ?string {
        if (!isset($fileInfo['tmp_name']) || empty($fileInfo['tmp_name']) || $fileInfo['error'] === UPLOAD_ERR_NO_FILE) {
            return $existingFilename;
        }

        $uploadDir = __DIR__ . '/../../public/audio/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                 throw new \Exception('خطا در ایجاد پوشه بارگذاری فایل صوتی.');
            }
        }

        $allowedMimeTypes = ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/mp4', 'audio/aac'];
        $fileMimeType = mime_content_type($fileInfo['tmp_name']);

        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            throw new \Exception('نوع فایل صوتی نامعتبر است (' . htmlspecialchars($fileMimeType) . '). مجاز: MP3, OGG, WAV, MP4, AAC.');
        }

        if ($fileInfo['size'] > 5 * 1024 * 1024) { // Max 5MB
            throw new \Exception('فایل صوتی خیلی بزرگ است (حداکثر 5 مگابایت).');
        }

        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('خطای بارگذاری فایل صوتی با کد: ' . $fileInfo['error']);
        }

        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        if (empty($extension) && $fileMimeType === 'audio/mpeg') $extension = 'mp3';
        elseif (empty($extension) && $fileMimeType === 'audio/ogg') $extension = 'ogg';
        elseif (empty($extension) && $fileMimeType === 'audio/wav') $extension = 'wav';
        elseif (empty($extension)) $extension = 'bin';

        $newFilename = uniqid('audio_', true) . '.' . strtolower($extension);
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($fileInfo['tmp_name'], $destination)) {
            if ($existingFilename && $existingFilename !== $newFilename && file_exists($uploadDir . $existingFilename)) {
                @unlink($uploadDir . $existingFilename);
            }
            return $newFilename;
        }
        throw new \Exception('خطا در انتقال فایل صوتی بارگذاری شده.');
    }

    private function deleteAudioFile(?string $filename): bool {
        if (empty($filename)) {
            return true;
        }
        $filePath = __DIR__ . '/../../public/audio/' . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true;
    }

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /login?error=" . urlencode("برای دسترسی به جعبه لایتنر لطفا ابتدا وارد شوید."));
            exit;
        }
        $this->currentUserId = $_SESSION['user_id'];
        $this->wordModel = new Word();
        $this->leitnerCardModel = new LeitnerCard();
    }

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
            $audioFile = $_FILES['audio_file'] ?? null;
            $audioFilename = null;

            if (empty($germanWord) || empty($translation)) {
                header("Location: /leitner/add?error=" . urlencode("کلمه آلمانی و ترجمه آن الزامی است."));
                exit;
            }
            if ($this->wordModel->findByGermanWord($germanWord, $this->currentUserId)) {
                header("Location: /leitner/add?error=" . urlencode("این کلمه از قبل در واژگان شما موجود است."));
                exit;
            }

            $pdo = $this->wordModel->getDbConnection();
            try {
                if ($audioFile && $audioFile['error'] !== UPLOAD_ERR_NO_FILE) {
                    $audioFilename = $this->handleAudioUpload($audioFile);
                }

                $pdo->beginTransaction();
                $wordId = $this->wordModel->create($this->currentUserId, $germanWord, $translation, $audioFilename);

                if ($wordId) {
                    // New cards start in Box 0, model's create() handles this.
                    if ($this->leitnerCardModel->create($wordId, $this->currentUserId)) {
                        $pdo->commit();
                        header("Location: /leitner/add?message=" . urlencode("کلمه با موفقیت اضافه شد و در جعبه آشنایی قرار گرفت."));
                        exit;
                    } else {
                        $pdo->rollBack();
                        if ($audioFilename) $this->deleteAudioFile($audioFilename);
                        header("Location: /leitner/add?error=" . urlencode("خطا در ایجاد کارت لایتنر."));
                        exit;
                    }
                } else {
                    $pdo->rollBack();
                    if ($audioFilename) $this->deleteAudioFile($audioFilename);
                    header("Location: /leitner/add?error=" . urlencode("خطا در افزودن کلمه."));
                    exit;
                }
            } catch (\Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if (isset($audioFilename) && $audioFilename) $this->deleteAudioFile($audioFilename);
                header("Location: /leitner/add?error=" . urlencode("یک خطا رخ داد: " . $e->getMessage()));
                exit;
            }
        }
        $this->showAddWordForm();
    }

    public function updateWord(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /leitner/vocabulary?error=" . urlencode("متد درخواست نامعتبر است."));
            exit;
        }

        $wordId = filter_input(INPUT_POST, 'word_id', FILTER_VALIDATE_INT);
        $germanWord = trim($_POST['german_word'] ?? '');
        $translation = trim($_POST['translation'] ?? '');
        $audioFile = $_FILES['audio_file'] ?? null;

        if (!isset($wordId) || $wordId === false) {
            header("Location: /leitner/vocabulary?error=" . urlencode("شناسه کلمه نامعتبر است."));
            exit;
        }

        if (empty($germanWord) || empty($translation)) {
            header("Location: /leitner/edit?id=$wordId&error=" . urlencode("همه‌ی فیلدها الزامی هستند."));
            exit;
        }

        $existingWord = $this->wordModel->findById($wordId, $this->currentUserId);
        if (!$existingWord) {
            header("Location: /leitner/vocabulary?error=" . urlencode("کلمه یافت نشد یا دسترسی مجاز نیست."));
            exit;
        }
        $currentAudioFilename = $existingWord['audio_filename'];

        $conflictWord = $this->wordModel->findByGermanWord($germanWord, $this->currentUserId);
        if ($conflictWord && $conflictWord['id'] !== $wordId) {
            header("Location: /leitner/edit?id=$wordId&error=" . urlencode("کلمه دیگری با این عبارت آلمانی موجود است."));
            exit;
        }

        $newAudioFilename = $currentAudioFilename;

        try {
            if ($audioFile && $audioFile['error'] !== UPLOAD_ERR_NO_FILE) {
                $newAudioFilename = $this->handleAudioUpload($audioFile, $currentAudioFilename);
            }

            if ($this->wordModel->update($wordId, $this->currentUserId, $germanWord, $translation, $newAudioFilename)) {
                header("Location: /leitner/vocabulary?message=" . urlencode("کلمه با موفقیت بروزرسانی شد."));
                exit;
            } else {
                header("Location: /leitner/edit?id=$wordId&error=" . urlencode("خطا در بروزرسانی کلمه."));
                exit;
            }
        } catch (\Exception $e) {
            header("Location: /leitner/edit?id=$wordId&error=" . urlencode("یک خطا رخ داد: " . $e->getMessage()));
            exit;
        }
    }

    public function deleteWord(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /leitner/vocabulary?error=" . urlencode("متد درخواست برای حذف نامعتبر است."));
            exit;
        }
        $wordId = filter_input(INPUT_POST, 'word_id', FILTER_VALIDATE_INT);
        if (!isset($wordId) || $wordId === false) {
            header("Location: /leitner/vocabulary?error=" . urlencode("شناسه کلمه برای حذف نامعتبر است."));
            exit;
        }

        $word = $this->wordModel->findById($wordId, $this->currentUserId);
        if (!$word) {
            header("Location: /leitner/vocabulary?error=" . urlencode("کلمه برای حذف یافت نشد یا دسترسی مجاز نیست."));
            exit;
        }

        $audioFilenameToDelete = $word['audio_filename'];

        if ($this->wordModel->delete($wordId, $this->currentUserId)) {
            if ($audioFilenameToDelete) {
                $this->deleteAudioFile($audioFilenameToDelete);
            }
            header("Location: /leitner/vocabulary?message=" . urlencode("کلمه با موفقیت حذف شد."));
            exit;
        } else {
            header("Location: /leitner/vocabulary?error=" . urlencode("خطا در حذف کلمه از پایگاه داده."));
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

        if (!isset($cardId) || $cardId === false ||
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
                case LeitnerCard::OUTCOME_CORRECT:
                    $message = "کارت بروز شد: پاسخ صحیح ثبت شد.";
                    break;
                case LeitnerCard::OUTCOME_PARTIAL:
                    $message = "کارت بروز شد: پاسخ نسبی ثبت شد.";
                    break;
                case LeitnerCard::OUTCOME_INCORRECT:
                    $message = "کارت بروز شد: پاسخ نادرست ثبت شد.";
                    break;
            }
            header("Location: /leitner/review?message=" . urlencode($message));
            exit;
        } else {
            header("Location: /leitner/review?error=" . urlencode("خطا در پردازش مرور. لطفا دوباره تلاش کنید."));
            exit;
        }
    }

    public function showVocabularyList(): void {
        $words = $this->wordModel->getAllByUser($this->currentUserId);
        $message = $_GET['message'] ?? null;
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../Views/leitner/list_vocabulary.php';
    }

    public function showEditWordForm(): void {
        $wordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!isset($wordId) || $wordId === false) {
            header("Location: /leitner/vocabulary?error=" . urlencode("شناسه کلمه نامعتبر است."));
            exit;
        }
        $word = $this->wordModel->findById($wordId, $this->currentUserId);
        if (!$word) {
            header("Location: /leitner/vocabulary?error=" . urlencode("کلمه یافت نشد یا دسترسی مجاز نیست."));
            exit;
        }
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../Views/leitner/edit_word.php';
    }
}
