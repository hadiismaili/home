<?php
// $username, $stats, $isReviewAvailable are passed from LeitnerController::showDashboard()
require_once __DIR__ . '/../partials/header.php';
?>

<h3>سلام <?php echo htmlspecialchars($username ?? 'کاربر'); ?>، به جعبه لایتنر خود خوش آمدید!</h3>

<h4>وضعیت جعبه‌های شما:</h4>
<?php if (isset($stats) && !empty($stats)): ?>
    <ul style="list-style-type: none; padding-left: 0; line-height: 1.6;">
        <li style="font-weight: bold;">جعبه آشنایی (Box 0): <?php echo htmlspecialchars($stats[0] ?? 0); ?> کارت</li>
        <?php for ($i = 1; $i <= App\Models\LeitnerCard::MAX_BOX_NUMBER; $i++): // MAX_BOX_NUMBER is 11 ?>
            <li>جعبه <?php echo $i; ?>: <?php echo htmlspecialchars($stats[$i] ?? 0); ?> کارت</li>
        <?php endfor; ?>
        <li style="font-weight: bold;">تکمیل شده (Mastered): <?php echo htmlspecialchars($stats[App\Models\LeitnerCard::MAX_BOX_NUMBER + 1] ?? 0); ?> کارت</li>
        <li style="margin-top:15px; padding-top:10px; border-top: 1px solid #eee;"><strong>کارت‌های آماده مرور: <?php echo htmlspecialchars($stats['due'] ?? 0); ?></strong></li>
    </ul>
<?php else: ?>
    <p>اطلاعات آماری برای نمایش وجود ندارد.</p>
<?php endif; ?>


<div style="margin-top:25px;">
    <a href="/leitner/add" class="button">افزودن کلمه جدید</a>
    <?php if (isset($isReviewAvailable) && $isReviewAvailable): ?>
        <a href="/leitner/review" class="button review-button">شروع مرور (<?php echo htmlspecialchars($stats['due'] ?? 0); ?> کارت)</a>
    <?php else: ?>
        <p style="display:inline-block; margin-left:10px; margin-right:10px; color: #555;">در حال حاضر کارتی برای مرور آماده نیست.</p>
    <?php endif; ?>
    <a href="/leitner/vocabulary" class="button" style="background-color:#5bc0de;">مدیریت واژگان</a>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
