<?php
require_once __DIR__ . '/../partials/header.php';
// $sets, $message, $error passed from controller (or via session flash)
?>

<h2>مدیریت مجموعه‌های آموزشی</h2>

<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<p><a href="/admin/learning-sets/add" class="button">ایجاد مجموعه جدید</a></p>

<?php if (!empty($sets)): ?>
    <div style="overflow-x:auto;">
    <table border="1" style="width:100%; min-width: 800px; border-collapse: collapse; margin-top:20px; font-size: 0.9em; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="padding: 10px; text-align:right;">شناسه</th>
                <th style="padding: 10px; text-align:right;">نام مجموعه</th>
                <th style="padding: 10px; text-align:right; max-width: 300px;">توضیحات</th>
                <th style="padding: 10px; text-align:center;">تعداد کلمات</th>
                <th style="padding: 10px; text-align:right;">ایجاد کننده</th>
                <th style="padding: 10px; text-align:right;">تاریخ ایجاد</th>
                <th style="padding: 10px; text-align:center; min-width:130px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sets as $set): ?>
                <tr>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($set['id']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($set['name']); ?></td>
                    <td style="padding: 8px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($set['description'] ?? ''); ?>"><?php echo nl2br(htmlspecialchars($set['description'] ?? '---')); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($set['word_count'] ?? 0); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($set['admin_username'] ?? '---'); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($set['created_at']))); ?></td>
                    <td style="padding: 8px; text-align:center; white-space: nowrap;">
                        <a href="/admin/learning-sets/edit?id=<?php echo $set['id']; ?>" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#f0ad4e; margin-right: 3px; margin-bottom:3px;">ویرایش</a>
                        <form action="/admin/learning-sets/delete" method="POST" style="display:inline-block; margin-bottom:3px;" onsubmit="return confirm('آیا از حذف این مجموعه آموزشی (<?php echo htmlspecialchars(addslashes($set['name'])); ?>) مطمئن هستید؟ این عمل باعث حذف پیشرفت کاربران در این مجموعه نیز خواهد شد.');">
                            <input type="hidden" name="id" value="<?php echo $set['id']; ?>">
                            <button type="submit" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#d9534f;">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <p>هنوز هیچ مجموعه آموزشی ایجاد نشده است.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="/admin/dashboard" class="button">بازگشت به داشبورد ادمین</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
