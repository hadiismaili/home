<?php
namespace App\Controllers\Admin;

use App\Models\GlobalWord;
// Assuming BaseAdminController handles admin auth and provides $this->currentUserId

class GlobalWordController extends BaseAdminController {
    private GlobalWord $globalWordModel;
    private const WORDS_PER_PAGE = 20;

    public function __construct() {
        parent::__construct();
        $this->globalWordModel = new GlobalWord();
    }

    public function listWords(): void {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        if ($page === false) $page = 1;
        $offset = ($page - 1) * self::WORDS_PER_PAGE;

        $words = $this->globalWordModel->getAll(self::WORDS_PER_PAGE, $offset, 'german_word', 'ASC');
        $totalWords = $this->globalWordModel->countAll();

        $totalPages = ceil($totalWords / self::WORDS_PER_PAGE);

        $message = $_SESSION['flash_message'] ?? null; unset($_SESSION['flash_message']);
        $error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
        // Note: Detailed CSV errors are handled separately in the view via $_SESSION['csv_error_details']

        require_once __DIR__ . '/../../Views/admin/global_words/list.php';
    }

    public function showAddForm(): void {
        $word = $_SESSION['old_input'] ?? null; unset($_SESSION['old_input']); // Use old_input for repopulation
        $formAction = '/admin/global-words/add';
        $formTitle = 'افزودن کلمه جدید به بانک جهانی';
        $submitButtonText = 'افزودن کلمه';
        $error = $_SESSION['form_error'] ?? null; unset($_SESSION['form_error']);
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
            $_SESSION['flash_message'] = "کلمه با موفقیت به بانک جهانی اضافه شد.";
        } else {
            // If create fails for other reasons (e.g. DB error not caught by model)
            $_SESSION['form_error'] = "خطا در افزودن کلمه به بانک جهانی. جزئیات ممکن است در لاگ سرور موجود باشد.";
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/global-words/add"); exit;
        }
        header("Location: /admin/global-words");
        exit;
    }

    public function showEditForm(): void {
        $wordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            $_SESSION['flash_error'] = "شناسه کلمه نامعتبر است.";
            header("Location: /admin/global-words"); exit;
        }

        $word = $_SESSION['old_input'] ?? $this->globalWordModel->findById($wordId);
        if(isset($_SESSION['old_input'])) unset($_SESSION['old_input']);


        if (!$word) {
            $_SESSION['flash_error'] = "کلمه مورد نظر یافت نشد.";
            header("Location: /admin/global-words"); exit;
        }
        $formAction = "/admin/global-words/edit"; // POST to same base URL, ID is in hidden field
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
            $_SESSION['flash_error'] = "شناسه کلمه برای بروزرسانی نامعتبر است.";
            header("Location: /admin/global-words"); exit;
        }

        $data = $this->getWordDataFromPost();

        if (empty($data['german_word']) || empty($data['translation'])) {
            $_SESSION['form_error'] = "کلمه آلمانی و ترجمه فارسی الزامی هستند.";
            $_SESSION['old_input'] = array_merge(['id' => $wordId], $_POST); // Preserve ID
            header("Location: /admin/global-words/edit?id=" . $wordId); exit;
        }

        $existingWord = $this->globalWordModel->findByGermanWord($data['german_word']);
        if ($existingWord && (int)$existingWord['id'] !== $wordId) {
            $_SESSION['form_error'] = "کلمه‌ای دیگر با این عبارت آلمانی از قبل در بانک جهانی موجود است.";
            $_SESSION['old_input'] = array_merge(['id' => $wordId], $_POST);
            header("Location: /admin/global-words/edit?id=" . $wordId); exit;
        }

        if ($this->globalWordModel->update($wordId, $data)) {
            $_SESSION['flash_message'] = "کلمه با موفقیت بروزرسانی شد.";
        } else {
            $_SESSION['flash_error'] = "خطا در بروزرسانی کلمه یا تغییری ایجاد نشد.";
        }
        header("Location: /admin/global-words"); // Redirect to list view after update attempt
        exit;
    }

    public function deleteWord(): void {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = "متد نامعتبر برای حذف.";
            header("Location: /admin/global-words"); exit;
        }
        $wordId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$wordId || $wordId === false) {
            $_SESSION['flash_error'] = "شناسه کلمه برای حذف نامعتبر است.";
            header("Location: /admin/global-words"); exit;
        }
        if ($this->globalWordModel->delete($wordId)) {
            $_SESSION['flash_message'] = "کلمه با موفقیت از بانک جهانی حذف شد.";
        } else {
            $_SESSION['flash_error'] = "خطا در حذف کلمه از بانک جهانی.";
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

    public function importCsv(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = "متد نامعتبر برای بارگذاری.";
            header("Location: /admin/global-words");
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = "خطا در بارگذاری فایل. لطفا مطمئن شوید یک فایل انتخاب شده و بدون خطا بارگذاری شده است.";
            header("Location: /admin/global-words");
            exit;
        }

        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $fileSize = $_FILES['csv_file']['size'];
        $fileTypeReported = $_FILES['csv_file']['type'];
        $fileTypeDetected = mime_content_type($fileTmpPath);

        $allowedMimeTypes = ['text/csv', 'application/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream'];
        if (!in_array($fileTypeDetected, $allowedMimeTypes) && !in_array($fileTypeReported, $allowedMimeTypes) ) {
            $_SESSION['flash_error'] = "فرمت فایل نامعتبر است. لطفا یک فایل CSV معتبر انتخاب کنید. (تشخیص: " . htmlspecialchars($fileTypeDetected) . ", گزارش شده: " . htmlspecialchars($fileTypeReported) . ")";
            header("Location: /admin/global-words");
            exit;
        }

        if ($fileSize > 5 * 1024 * 1024) { // Max 5MB
            $_SESSION['flash_error'] = "فایل CSV بیش از حد بزرگ است (حداکثر 5 مگابایت).";
            header("Location: /admin/global-words");
            exit;
        }

        $importedCount = 0;
        $skippedCount = 0;
        $errorRows = [];
        $lineNumber = 0;

        $expectedHeaders = [
            'german_word', 'translation', 'persian_phonetic_pronunciation',
            'word_type', 'word_gender', 'word_level',
            'example_german', 'example_persian_translation', 'audio_url'
        ];
        $headerMap = [];

        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            $pdo = $this->globalWordModel->getDbConnection();
            try {
                $pdo->beginTransaction();

                while (($rowData = fgetcsv($handle, 2048, ',')) !== FALSE) {
                    $lineNumber++;
                    if ($lineNumber === 1) {
                        foreach ($rowData as $index => $headerName) {
                            $trimmedHeader = trim($headerName);
                            if ($index === 0) { // Remove BOM from first header only
                                $bom = pack('H*','EFBBBF');
                                $trimmedHeader = preg_replace("/^$bom/", '', $trimmedHeader);
                            }
                            if (in_array($trimmedHeader, $expectedHeaders)) {
                                $headerMap[$trimmedHeader] = $index;
                            }
                        }
                        if (!isset($headerMap['german_word']) || !isset($headerMap['translation'])) {
                            throw new \Exception("فایل CSV باید شامل ستون‌های الزامی 'german_word' و 'translation' در ردیف هدر باشد.");
                        }
                        continue;
                    }

                    $data = [];
                    foreach ($expectedHeaders as $expectedHeader) {
                        $columnIndex = $headerMap[$expectedHeader] ?? null;
                        $data[$expectedHeader] = ($columnIndex !== null && isset($rowData[$columnIndex]))
                                                  ? trim($rowData[$columnIndex])
                                                  : null;
                    }

                    if (empty($data['german_word']) || empty($data['translation'])) {
                        $skippedCount++;
                        $errorRows[] = "ردیف $lineNumber: کلمه آلمانی و ترجمه الزامی هستند.";
                        continue;
                    }

                    if ($this->globalWordModel->findByGermanWord($data['german_word'])) {
                        $skippedCount++;
                        $errorRows[] = "ردیف $lineNumber: کلمه '" . htmlspecialchars($data['german_word']) . "' از قبل موجود است.";
                        continue;
                    }

                    foreach (['persian_phonetic_pronunciation', 'word_type', 'word_gender', 'word_level', 'example_german', 'example_persian_translation', 'audio_url'] as \$optionalField) {
                        if (empty(\$data[\$optionalField])) {
                            \$data[\$optionalField] = null;
                        }
                    }

                    if ($this->globalWordModel->create($data)) {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                        $errorRows[] = "ردیف $lineNumber: خطا در ذخیره کلمه '" . htmlspecialchars($data['german_word']) . "' در پایگاه داده.";
                    }
                }
                $pdo->commit();
                $_SESSION['flash_message'] = "پردازش CSV تکمیل شد. $importedCount کلمه با موفقیت بارگذاری شد. $skippedCount کلمه نادیده گرفته شد.";
            } catch (\Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['flash_error'] = "خطا در پردازش فایل CSV: " . $e->getMessage();
            } finally {
                fclose($handle);
            }
        } else {
            $_SESSION['flash_error'] = "خطا در باز کردن فایل CSV.";
        }

        if (!empty($errorRows)) {
            $_SESSION['csv_error_details'] = $errorRows;
        }

        header("Location: /admin/global-words");
        exit;
    }
}
