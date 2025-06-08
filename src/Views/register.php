<?php require_once __DIR__ . '/partials/header.php'; ?>

<h2>ثبت نام کاربر جدید</h2>

<?php if (!empty($_GET['error'])): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></p>
<?php endif; ?>
<?php if (!empty($_GET['message'])): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
<?php endif; ?>

<form action="/register" method="POST">
    <div>
        <label for="username">نام کاربری:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <div>
        <label for="email">ایمیل:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div>
        <label for="password">رمز عبور (حداقل ۶ کاراکتر):</label>
        <input type="password" id="password" name="password" required minlength="6">
    </div>
    <div>
        <label for="confirm_password">تکرار رمز عبور:</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
    </div>
    <button type="submit">ثبت نام</button>
</form>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
