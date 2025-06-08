<?php
// Passed from GlobalWordController: $words, $totalPages, $page
// Flash messages $message, $error are now fetched from session in controller and passed directly
require_once __DIR__ . '/../partials/header.php'; // Admin header
?>

<h2>مدیریت بانک جهانی کلمات</h2>

<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid #a3d9a5; background-color: #e9f5e9; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid #f5c6cb; background-color: #f8d7da; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php
if (!empty($_SESSION['csv_error_details'])):
    $csvErrors = $_SESSION['csv_error_details'];
    unset($_SESSION['csv_error_details']); // Clear it after fetching
?>
    <div class="csv-error-details" style="margin-top: 15px; margin-bottom:15px; padding: 15px; border: 1px solid #d9534f; background-color: #f2dede; color: #a94442; border-radius: 4px;">
        <strong style="display:block; margin-bottom:8px;">جزئیات خطاهای بارگذاری CSV:</strong>
        <ul style="max-height: 200px; overflow-y: auto; margin:0; padding-right: 20px;">
            <?php foreach ($csvErrors as $csvError): ?>
                <li style="margin-bottom: 4px;"><?php echo htmlspecialchars($csvError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<p><a href="/admin/global-words/add" class="button">افزودن کلمه جدید به بانک</a></p>

<div class="csv-upload-section" style="margin-top: 20px; margin-bottom: 30px; padding: 20px; border: 1px solid #ccc; border-radius: 5px; background-color: #f0f0f0;">
    <h4>بارگذاری کلمات از طریق فایل CSV</h4>
    <p style="font-size:0.9em; color: #555; line-height:1.6;">
        فایل CSV شما باید شامل ستون‌های زیر باشد. ردیف اول به عنوان هدر در نظر گرفته می‌شود و باید با نام‌های دقیق زیر مطابقت داشته باشد: <br>
        <strong>ستون‌های الزامی:</strong> <code>german_word</code>, <code>translation</code><br>
        <strong>ستون‌های اختیاری:</strong> <code>persian_phonetic_pronunciation</code>, <code>word_type</code>, <code>word_gender</code>, <code>word_level</code>, <code>example_german</code>, <code>example_persian_translation</code>, <code>audio_url</code>
    </p>
    <form action="/admin/global-words/import" method="POST" enctype="multipart/form-data">
        <div>
            <label for="csv_file" style="display:block; margin-bottom:8px;">انتخاب فایل CSV:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="form-control" style="padding:8px; border:1px solid #ccc; border-radius:4px; display: inline-block; width: auto; margin-bottom:10px;">
        </div>
        <button type="submit" class="button">بارگذاری و پردازش CSV</button>
    </form>
</div>

<?php if (!empty($words)): ?>
    <div style="overflow-x:auto;">
    <table border="1" style="width:100%; min-width: 900px; border-collapse: collapse; margin-top:20px; font-size: 0.85em; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="padding: 10px; text-align:right;">آلمانی</th>
                <th style="padding: 10px; text-align:right;">ترجمه</th>
                <th style="padding: 10px; text-align:center;">سطح</th>
                <th style="padding: 10px; text-align:center;">نوع</th>
                <th style="padding: 10px; text-align:center;">جنسیت</th>
                <th style="padding: 10px; text-align:right;">تلفظ فارسی</th>
                <th style="padding: 10px; text-align:center; min-width:130px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($words as $word): ?>
                <tr>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['german_word']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['translation']); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($word['word_level'] ?? '---'); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($word['word_type'] ?? '---'); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($word['word_gender'] ?? '---'); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['persian_phonetic_pronunciation'] ?? '---'); ?></td>
                    <td style="padding: 8px; text-align:center; white-space: nowrap;">
                        <a href="/admin/global-words/edit?id=<?php echo $word['id']; ?>" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#f0ad4e; margin-right: 3px; margin-bottom:3px;">ویرایش</a>
                        <form action="/admin/global-words/delete" method="POST" style="display:inline-block; margin-bottom:3px;" onsubmit="return confirm('آیا از حذف این کلمه (<?php echo htmlspecialchars(addslashes($word['german_word'])); ?>) از بانک جهانی مطمئن هستید؟ این عمل ممکن است بر ست‌های آموزشی و پیشرفت کاربران تاثیر بگذارد.');">
                            <input type="hidden" name="id" value="<?php echo $word['id']; ?>">
                            <button type="submit" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#d9534f;">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination" style="margin-top: 20px; text-align:center;">
            <?php if (isset($page) && $page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?>" class="button" style="padding: 8px 12px;">&laquo; قبلی</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?>" class="button <?php echo (isset($page) && $i == $page) ? 'active' : ''; ?>"
                   style="padding: 8px 12px; <?php echo (isset($page) && $i == $page) ? 'background-color:#2c3e50; color:white; font-weight:bold;' : ''; ?>">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if (isset($page) && $page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?>" class="button" style="padding: 8px 12px;">بعدی &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p>هنوز هیچ کلمه‌ای در بانک جهانی وجود ندارد. برای شروع، یک کلمه جدید اضافه کنید.</p>
<?php endif; ?>

<p style="margin-top:30px;"><a href="/admin/dashboard" class="button">بازگشت به داشبورد ادمین</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
