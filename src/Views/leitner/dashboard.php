<?php
// Assuming $username, $stats, $isReviewAvailable are passed from LeitnerController::showDashboard()
require_once __DIR__ . '/../partials/header.php';
?>

<h3>سلام <?php echo htmlspecialchars($username); ?>، به جعبه لایتنر خود خوش آمدید!</h3>

<h4>وضعیت جعبه‌ها:</h4>
<ul>
    <?php for ($i = 1; $i <= App\Models\LeitnerCard::MAX_BOX_NUMBER; $i++): ?>
        <li>جعبه <?php echo $i; ?>: <?php echo $stats[$i] ?? 0; ?> کارت</li>
    <?php endfor; ?>
    <li>کارت‌های آماده مرور: <strong><?php echo $stats['due'] ?? 0; ?></strong></li>
</ul>

<div>
    <a href="/leitner/add" class="button">افزودن کلمه جدید</a>
    <a href="/leitner/vocabulary" class="button" style="background-color:#5bc0de;">مدیریت واژگان</a>
    <?php if ($isReviewAvailable): ?>
        <a href="/leitner/review" class="button review-button">شروع مرور (<?php echo $stats['due']; ?> کارت)</a>
    <?php else: ?>
        <p>در حال حاضر کارتی برای مرور آماده نیست.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
