<?php
// Passed from GlobalWordController: $words, $totalPages, $page, $message, $error
require_once __DIR__ . '/../partials/header.php'; // Admin header
?>

<h2>مدیریت بانک جهانی کلمات</h2>

<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($message)); ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>

<p><a href="/admin/global-words/add" class="button">افزودن کلمه جدید به بانک</a></p>

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
                <a href="?page=<?php echo $page - 1; ?>" class="button" style="padding: 8px 12px;">&laquo; قبلی</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="button <?php echo (isset($page) && $i == $page) ? 'active' : ''; ?>"
                   style="padding: 8px 12px; <?php echo (isset($page) && $i == $page) ? 'background-color:#2c3e50; color:white; font-weight:bold;' : ''; ?>">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if (isset($page) && $page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="button" style="padding: 8px 12px;">بعدی &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p>هنوز هیچ کلمه‌ای در بانک جهانی وجود ندارد. برای شروع، یک کلمه جدید اضافه کنید.</p>
<?php endif; ?>

<p style="margin-top:30px;"><a href="/admin/dashboard" class="button">بازگشت به داشبورد ادمین</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
