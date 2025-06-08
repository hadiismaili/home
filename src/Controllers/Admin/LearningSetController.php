<?php
namespace App\Controllers\Admin;

use App\Models\LearningSet;
use App\Models\GlobalWord;

class LearningSetController extends BaseAdminController {
    private LearningSet $learningSetModel;
    private GlobalWord $globalWordModel;

    public function __construct() {
        parent::__construct();
        $this->learningSetModel = new LearningSet();
        $this->globalWordModel = new GlobalWord();
    }

    public function listSets(): void {
        $sets = $this->learningSetModel->getAll('name', 'ASC');

        $message = $_SESSION['flash_message'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_message'], $_SESSION['flash_error']);

        require_once __DIR__ . '/../../Views/admin/learning_sets/list.php';
    }

    public function showAddForm(): void {
        $set = $_SESSION['old_input_set'] ?? null; unset($_SESSION['old_input_set']);
        $formAction = '/admin/learning-sets/add';
        $formTitle = 'ایجاد مجموعه آموزشی جدید';
        $submitButtonText = 'ایجاد مجموعه';
        $assignedWordIds = $_SESSION['assigned_word_ids'] ?? []; unset($_SESSION['assigned_word_ids']);
        $availableWords = $this->globalWordModel->getAll(10000, 0, 'german_word', 'ASC'); // Get a large number of words

        $error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        require_once __DIR__ . '/../../Views/admin/learning_sets/form.php';
    }

    public function addSet(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/learning-sets"); exit;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $adminId = $this->currentUserId;
        $selectedWordIds = $_POST['word_ids'] ?? [];

        if (empty($name)) {
            $_SESSION['flash_error'] = "نام مجموعه آموزشی الزامی است.";
            $_SESSION['old_input_set'] = $_POST; // Save input for repopulation
            $_SESSION['assigned_word_ids'] = $selectedWordIds; // Save selected words
            header("Location: /admin/learning-sets/add"); exit;
        }
        if ($this->learningSetModel->findByName($name)) {
            $_SESSION['flash_error'] = "مجموعه‌ای با این نام از قبل موجود است.";
            $_SESSION['old_input_set'] = $_POST;
            $_SESSION['assigned_word_ids'] = $selectedWordIds;
            header("Location: /admin/learning-sets/add"); exit;
        }

        $pdo = $this->learningSetModel->getDbConnection();
        try {
            $pdo->beginTransaction();
            $setId = $this->learningSetModel->create($name, $description, $adminId);
            if (!$setId) {
                throw new \Exception("خطا در ایجاد مجموعه آموزشی.");
            }
            foreach ($selectedWordIds as $wordId) {
                if (!empty($wordId) && !$this->learningSetModel->addWordToSet($setId, (int)$wordId)) {
                    throw new \Exception("خطا در افزودن کلمه با شناسه " . (int)$wordId . " به مجموعه.");
                }
            }
            $pdo->commit();
            $_SESSION['flash_message'] = "مجموعه آموزشی با موفقیت ایجاد شد.";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error'] = "خطای سیستمی: " . $e->getMessage();
             $_SESSION['old_input_set'] = $_POST;
             $_SESSION['assigned_word_ids'] = $selectedWordIds;
            header("Location: /admin/learning-sets/add"); exit;
        }
        header("Location: /admin/learning-sets");
        exit;
    }

    public function showEditForm(): void {
        $setId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$setId || $setId === false) {
            $_SESSION['flash_error'] = "شناسه مجموعه نامعتبر است.";
            header("Location: /admin/learning-sets"); exit;
        }

        // Use session for old input if validation failed during update
        $set = $_SESSION['old_input_set'] ?? $this->learningSetModel->findById($setId);
        unset($_SESSION['old_input_set']);

        if (!$set) {
            $_SESSION['flash_error'] = "مجموعه مورد نظر یافت نشد.";
            header("Location: /admin/learning-sets"); exit;
        }

        $formAction = "/admin/learning-sets/edit?id=" . $setId;
        $formTitle = "ویرایش مجموعه آموزشی: " . htmlspecialchars($set['name']);
        $submitButtonText = 'ذخیره تغییرات';

        $assignedWordIds = $_SESSION['assigned_word_ids'] ?? $this->learningSetModel->getWordsInSet($setId, true);
        unset($_SESSION['assigned_word_ids']);
        $availableWords = $this->globalWordModel->getAll(10000, 0, 'german_word', 'ASC');

        $error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        require_once __DIR__ . '/../../Views/admin/learning_sets/form.php';
    }

    public function updateSet(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/learning-sets"); exit;
        }
        $setId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$setId || $setId === false) {
            $_SESSION['flash_error'] = "شناسه مجموعه برای بروزرسانی نامعتبر است.";
            header("Location: /admin/learning-sets"); exit;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $selectedWordIds = $_POST['word_ids'] ?? [];

        if (empty($name)) {
            $_SESSION['flash_error'] = "نام مجموعه آموزشی الزامی است.";
            $_SESSION['old_input_set'] = $_POST;
            $_SESSION['assigned_word_ids'] = $selectedWordIds;
            header("Location: /admin/learning-sets/edit?id=" . $setId); exit;
        }

        $existingSetWithName = $this->learningSetModel->findByName($name);
        if ($existingSetWithName && (int)$existingSetWithName['id'] !== $setId) { // Cast to int for strict comparison
             $_SESSION['flash_error'] = "مجموعه‌ای دیگر با این نام از قبل موجود است.";
             $_SESSION['old_input_set'] = $_POST;
             $_SESSION['assigned_word_ids'] = $selectedWordIds;
             header("Location: /admin/learning-sets/edit?id=" . $setId); exit;
        }

        $pdo = $this->learningSetModel->getDbConnection();
        try {
            $pdo->beginTransaction();
            $this->learningSetModel->update($setId, $name, $description); // Update name/description

            $currentWordsInSet = $this->learningSetModel->getWordsInSet($setId, true);

            // Words to remove
            foreach($currentWordsInSet as $currentWordId){
                if(!in_array($currentWordId, $selectedWordIds)){
                    $this->learningSetModel->removeWordFromSet($setId, $currentWordId);
                }
            }
            // Words to add
            foreach ($selectedWordIds as $wordId) {
                if(!empty($wordId) && !in_array($wordId, $currentWordsInSet)){ // Check if not already in set to avoid duplicate error
                    if (!\$this->learningSetModel->addWordToSet(\$setId, (int)\$wordId)) {
                        throw new \Exception("خطا در بروزرسانی کلمات مجموعه (افزودن کلمه " . (int)\$wordId . ").");
                    }
                }
            }
            $pdo->commit();
            $_SESSION['flash_message'] = "مجموعه آموزشی با موفقیت بروزرسانی شد.";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error'] = "خطای سیستمی: " . $e->getMessage();
            $_SESSION['old_input_set'] = $_POST; // Keep form data on error
            $_SESSION['assigned_word_ids'] = $selectedWordIds;
        }
        header("Location: /admin/learning-sets/edit?id=" . $setId);
        exit;
    }

    public function deleteSet(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $_SESSION['flash_error'] = "متد نامعتبر برای حذف."; // Use flash for consistency
            header("Location: /admin/learning-sets"); exit;
        }
        $setId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$setId || $setId === false) {
            $_SESSION['flash_error'] = "شناسه مجموعه برای حذف نامعتبر است.";
            header("Location: /admin/learning-sets"); exit;
        }
        if ($this->learningSetModel->delete($setId)) {
            $_SESSION['flash_message'] = "مجموعه آموزشی با موفقیت حذف شد.";
        } else {
            $_SESSION['flash_error'] = "خطا در حذف مجموعه آموزشی.";
        }
        header("Location: /admin/learning-sets");
        exit;
    }
}
