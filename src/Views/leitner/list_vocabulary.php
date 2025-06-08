<?php
require_once __DIR__ . '/../partials/header.php';
// $words, $message, $error are passed from LeitnerController
?>

<h2>مدیریت واژگان</h2>

<?php if (!empty($_GET['message'])): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></p>
<?php endif; ?>

<p><a href="/leitner/add" class="button">افزودن کلمه جدید</a></p>

<?php if (!empty($words)): ?>
    <div style="overflow-x:auto;">
    <table border="1" style="width:100%; min-width: 800px; border-collapse: collapse; margin-top:20px; font-size: 0.85em; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="padding: 10px; text-align:right;">کلمه آلمانی</th>
                <th style="padding: 10px; text-align:right;">ترجمه فارسی</th>
                <th style="padding: 10px; text-align:right;">سطح</th>
                <th style="padding: 10px; text-align:right;">نوع/جنسیت</th>
                <th style="padding: 10px; text-align:right;">تلفظ</th>
                <th style="padding: 10px; text-align:right;">تاریخ ایجاد</th>
                <th style="padding: 10px; text-align:center; min-width:160px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($words as $word): ?>
                <tr>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['german_word']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['translation']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['word_level'] ?? '---'); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['word_type_and_gender'] ?? '---'); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($word['persian_phonetic_pronunciation'] ?? '---'); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($word['created_at']))); ?></td>
                    <td style="padding: 8px; text-align:center; white-space: nowrap;">
                        <a href="/leitner/edit?id=<?php echo $word['id']; ?>" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#f0ad4e; margin-right: 3px; margin-bottom: 3px;">ویرایش</a>
                        <form action="/leitner/delete" method="POST" style="display:inline-block; margin-bottom: 3px;" onsubmit="return confirm('آیا از حذف این کلمه مطمئن هستید؟ این عمل غیرقابل بازگشت است و کارت لایتنر مرتبط نیز حذف خواهد شد.');">
                            <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                            <button type="submit" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#d9534f;">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <p>هنوز هیچ کلمه‌ای به واژگان خود اضافه نکرده‌اید.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="/leitner/dashboard" class="button">بازگشت به داشبورد</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
