<?php
// Session check is now in AuthController->showHome()
require_once __DIR__ . '/partials/header.php';
?>

<h3>خوش آمدید, <?php echo htmlspecialchars($username); ?>!</h3>
<p>اینجا صفحه اصلی شما پس از ورود است. مدیریت جعبه لایتنر شما در اینجا خواهد بود.</p>
<p><a href="/logout">خروج</a></p>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
