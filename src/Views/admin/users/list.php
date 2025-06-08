<?php
// Passed from Admin\UserController: $users (now with stats), $message, $error
require_once __DIR__ . '/../partials/header.php';
?>

<h2>مدیریت کاربران سیستم</h2>

<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($message)); ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>

<?php if (!empty($users)): ?>
    <div style="overflow-x: auto;"> <!-- For smaller screens -->
    <table border="1" style="width:100%; min-width: 800px; border-collapse: collapse; margin-top:20px; font-size: 0.85em; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="padding: 10px; text-align:right;">شناسه</th>
                <th style="padding: 10px; text-align:right;">نام کاربری</th>
                <th style="padding: 10px; text-align:right;">ایمیل</th>
                <th style="padding: 10px; text-align:center;">ادمین</th>
                <th style="padding: 10px; text-align:right;">تاریخ عضویت</th>
                <th style="padding: 10px; text-align:center;">کلمات</th>
                <th style="padding: 10px; text-align:center;">کارت‌ها</th>
                <th style="padding: 10px; text-align:center;">آماده مرور (کاربر)</th>
                <th style="padding: 10px; text-align:center; min-width: 190px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($user['id']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo (bool)$user['is_admin'] ? 'بله' : 'خیر'; ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($user['created_at']))); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($user['word_count'] ?? 0); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($user['card_count'] ?? 0); ?></td>
                    <td style="padding: 8px; text-align:center;"><?php echo htmlspecialchars($user['due_cards_count'] ?? 0); ?></td>
                    <td style="padding: 8px; text-align:center; white-space: nowrap;">
                        <form action="/admin/users/toggle-admin" method="POST" style="display:inline-block; margin-right:5px; margin-bottom: 5px;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="button" style="padding: 5px 8px; font-size:0.9em; background-color: <?php echo (bool)$user['is_admin'] ? '#f0ad4e' : '#5cb85c'; ?>;">
                                <?php echo (bool)$user['is_admin'] ? 'لغو ادمین' : 'اعطای ادمین'; ?>
                            </button>
                        </form>
                        <?php
                        $currentLoggedInUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
                        if ($currentLoggedInUserId !== (int)$user['id']):
                        ?>
                        <form action="/admin/users/delete" method="POST" style="display:inline-block; margin-bottom: 5px;" onsubmit="return confirm('آیا از حذف کاربر <?php echo htmlspecialchars(addslashes($user['username'])); ?> مطمئن هستید؟ تمام داده‌های این کاربر (کلمات، کارت‌ها) نیز حذف خواهند شد.');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="button" style="padding: 5px 8px; font-size:0.9em; background-color:#d9534f;">حذف</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <p>هیچ کاربری در سیستم یافت نشد.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="/admin/dashboard" class="button">بازگشت به داشبورد ادمین</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
