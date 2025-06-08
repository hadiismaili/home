<?php
// Passed from LeitnerController: $words, $message, $error
require_once __DIR__ . '/../partials/header.php';
?>

<h2>مدیریت واژگان</h2>

<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($message)); ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>

<p><a href="/leitner/add" class="button">افزودن کلمه جدید</a></p>

<?php if (!empty($words)): ?>
    <table border="1" style="width:100%; border-collapse: collapse; margin-top:20px;">
        <thead>
            <tr>
                <th>کلمه آلمانی</th>
                <th>ترجمه فارسی</th>
                <th>تاریخ ایجاد</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($words as $word): ?>
                <tr>
                    <td><?php echo htmlspecialchars($word['german_word']); ?></td>
                    <td><?php echo htmlspecialchars($word['translation']); ?></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($word['created_at']))); ?></td>
                    <td>
                        <a href="/leitner/edit?id=<?php echo $word['id']; ?>" class="button" style="background-color:#f0ad4e;">ویرایش</a>
                        <form action="/leitner/delete" method="POST" style="display:inline;" onsubmit="return confirm('آیا از حذف این کلمه مطمئن هستید؟ این عمل غیرقابل بازگشت است.');">
                            <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                            <button type="submit" class="button" style="background-color:#d9534f;">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>هنوز هیچ کلمه‌ای به واژگان خود اضافه نکرده‌اید.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="/leitner/dashboard" class="button">بازگشت به داشبورد</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
