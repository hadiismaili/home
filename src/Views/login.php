<?php require_once __DIR__ . '/partials/header.php'; ?>

<h2>ورود به حساب کاربری</h2>

<?php if (!empty($_GET['error'])): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></p>
<?php endif; ?>
<?php if (!empty($_GET['message'])): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
<?php endif; ?>

<form action="/login" method="POST">
    <div>
        <label for="username">نام کاربری:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <div>
        <label for="password">رمز عبور:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">ورود</button>
</form>
<p>هنوز حساب کاربری ندارید؟ <a href="/register">ثبت نام کنید</a></p>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
