<?php
namespace App\Controllers\Admin;

use App\Models\GlobalWord;
// Assuming BaseAdminController handles admin auth and provides $this->currentUserId
// No direct View class used here, views are required directly.

class GlobalWordController extends BaseAdminController {
    private GlobalWord $globalWordModel;
    private const WORDS_PER_PAGE = 20;

    public function __construct() {
        parent::__construct();
        $this->globalWordModel = new GlobalWord();
    }

    public function listWords(): void {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        if ($page === false) $page = 1; // Ensure page is an int
        $offset = ($page - 1) * self::WORDS_PER_PAGE;

        // Search functionality can be enhanced later.
        // $searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
        // For now, basic listing:
        $words = $this->globalWordModel->getAll(self::WORDS_PER_PAGE, $offset, 'german_word', 'ASC');
        $totalWords = $this->globalWordModel->countAll();

        $totalPages = ceil($totalWords / self::WORDS_PER_PAGE);

        $message = $_SESSION['message'] ?? null; unset($_SESSION['message']); // Flash message
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);     // Flash message

        require_once __DIR__ . '/../../Views/admin/global_words/list.php';
    }

    public function showAddForm(): void {
        $word = null;
        $formAction = '/admin/global-words/add';
        $formTitle = 'افزودن کلمه جدید به بانک جهانی';
        $submitButtonText = 'افزودن کلمه';
        $error = $_SESSION['form_error'] ?? null; unset($_SESSION['form_error']);
        $oldInput = $_SESSION['old_input'] ?? []; unset($_SESSION['old_input']);
        require_once __DIR__ . '/../../Views/admin/global_words/form.php';
    }

    public function addWord(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/global-words"); exit;
        }

        $data = $this->getWordDataFromPost();

        if (empty($data['german_word']) || empty($data['translation'])) {
            $_SESSION['form_error'] = "کلمه آلمانی و ترجمه فارسی الزامی هستند.";
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/global-words/add"); exit;
        }

        if ($this->globalWordModel->findByGermanWord($data['german_word'])) {
             $_SESSION['form_error'] = "کلمه‌ای با این عبارت آلمانی از قبل در بانک جهانی موجود است.";
             $_SESSION['old_input'] = $_POST;
             header("Location: /admin/global-words/add"); exit;
        }

        if ($this->globalWordModel->create($data)) {
            $_SESSION['message'] = "کلمه با موفقیت به بانک جهانی اضافه شد.";
        } else {
            $_SESSION['error'] = "خطا در افزودن کلمه به بانک جهانی.";
        }
        header("Location: /admin/global-words");
        exit;
    }

    public function showEditForm(): void {
        $wordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            $_SESSION['error'] = "شناسه کلمه نامعتبر است.";
            header("Location: /admin/global-words"); exit;
        }
        $word = $_SESSION['old_input'] ?? $this->globalWordModel->findById($wordId); // Use old input if available
        unset($_SESSION['old_input']);

        if (!$word) {
            $_SESSION['error'] = "کلمه مورد نظر یافت نشد.";
            header("Location: /admin/global-words"); exit;
        }
        $formAction = "/admin/global-words/edit?id=" . $wordId;
        $formTitle = "ویرایش کلمه در بانک جهانی: " . htmlspecialchars($word['german_word']);
        $submitButtonText = 'ذخیره تغییرات';
        $error = $_SESSION['form_error'] ?? null; unset($_SESSION['form_error']);
        require_once __DIR__ . '/../../Views/admin/global_words/form.php';
    }

    public function updateWord(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/global-words"); exit;
        }
        $wordId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            $_SESSION['error'] = "شناسه کلمه برای بروزرسانی نامعتبر است.";
            header("Location: /admin/global-words"); exit;
        }

        $data = $this->getWordDataFromPost();

        if (empty($data['german_word']) || empty($data['translation'])) {
            $_SESSION['form_error'] = "کلمه آلمانی و ترجمه فارسی الزامی هستند.";
            $_SESSION['old_input'] = array_merge(['id' => $wordId], $_POST);
            header("Location: /admin/global-words/edit?id=" . $wordId); exit;
        }

        $existingWord = $this->globalWordModel->findByGermanWord($data['german_word']);
        if ($existingWord && (int)$existingWord['id'] !== $wordId) { // Cast existingWord ID to int
            $_SESSION['form_error'] = "کلمه‌ای دیگر با این عبارت آلمانی از قبل در بانک جهانی موجود است.";
            $_SESSION['old_input'] = array_merge(['id' => $wordId], $_POST);
            header("Location: /admin/global-words/edit?id=" . $wordId); exit;
        }

        if ($this->globalWordModel->update($wordId, $data)) {
            $_SESSION['message'] = "کلمه با موفقیت بروزرسانی شد.";
        } else {
            $_SESSION['error'] = "خطا در بروزرسانی کلمه یا تغییری ایجاد نشد.";
        }
        header("Location: /admin/global-words");
        exit;
    }

    public function deleteWord(): void {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "متد نامعتبر برای حذف.";
            header("Location: /admin/global-words"); exit;
        }
        $wordId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            $_SESSION['error'] = "شناسه کلمه برای حذف نامعتبر است.";
            header("Location: /admin/global-words"); exit;
        }
        if ($this->globalWordModel->delete($wordId)) {
            $_SESSION['message'] = "کلمه با موفقیت از بانک جهانی حذف شد.";
        } else {
            $_SESSION['error'] = "خطا در حذف کلمه از بانک جهانی.";
        }
        header("Location: /admin/global-words");
        exit;
    }

    private function getWordDataFromPost(): array {
        return [
            'german_word' => trim($_POST['german_word'] ?? ''),
            'translation' => trim($_POST['translation'] ?? ''),
            'persian_phonetic_pronunciation' => trim($_POST['persian_phonetic_pronunciation'] ?? '') ?: null,
            'word_type' => trim($_POST['word_type'] ?? '') ?: null,
            'word_gender' => trim($_POST['word_gender'] ?? '') ?: null,
            'word_level' => trim($_POST['word_level'] ?? '') ?: null,
            'example_german' => trim($_POST['example_german'] ?? '') ?: null,
            'example_persian_translation' => trim($_POST['example_persian_translation'] ?? '') ?: null,
            'audio_url' => trim($_POST['audio_url'] ?? '') ?: null,
        ];
    }
}
