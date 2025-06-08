<?php
// $adminUsername and $stats passed from DashboardController
require_once __DIR__ . '/partials/header.php';
?>

<h2>به پنل مدیریت خوش آمدید، <?php echo htmlspecialchars($adminUsername); ?>!</h2>
<p>اینجا خلاصه‌ای از وضعیت سیستم را مشاهده می‌کنید:</p>

<div class="admin-stats" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-around; margin-bottom: 30px;">
    <div class="stat-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background-color: #f9f9f9; flex-basis: 220px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h4 style="font-size: 1.1em; color: #555; margin-bottom: 10px;">تعداد کل کاربران</h4>
        <p style="font-size: 2.5em; margin: 5px 0; color: #3498db;"><?php echo htmlspecialchars($stats['total_users'] ?? 0); ?></p>
    </div>
    <div class="stat-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background-color: #f9f9f9; flex-basis: 220px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h4 style="font-size: 1.1em; color: #555; margin-bottom: 10px;">تعداد کل کلمات</h4>
        <p style="font-size: 2.5em; margin: 5px 0; color: #27ae60;"><?php echo htmlspecialchars($stats['total_words'] ?? 0); ?></p>
    </div>
    <div class="stat-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background-color: #f9f9f9; flex-basis: 220px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h4 style="font-size: 1.1em; color: #555; margin-bottom: 10px;">میانگین کلمه برای هر کاربر</h4>
        <p style="font-size: 2.5em; margin: 5px 0; color: #8e44ad;"><?php echo htmlspecialchars($stats['avg_words_per_user'] ?? 0); ?></p>
    </div>
    <div class="stat-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background-color: #f9f9f9; flex-basis: 220px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h4 style="font-size: 1.1em; color: #555; margin-bottom: 10px;">تعداد کل کارت‌های لایتنر</h4>
        <p style="font-size: 2.5em; margin: 5px 0; color: #e67e22;"><?php echo htmlspecialchars($stats['total_cards'] ?? 0); ?></p>
    </div>
    <div class="stat-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background-color: #f9f9f9; flex-basis: 220px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h4 style="font-size: 1.1em; color: #555; margin-bottom: 10px;">کارت‌های آماده مرور (کل سیستم)</h4>
        <p style="font-size: 2.5em; margin: 5px 0; color: #c0392b;"><?php echo htmlspecialchars($stats['total_due_today'] ?? 0); ?></p>
    </div>
</div>

<div class="box-distribution" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background-color: #fdfdfd; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h4 style="font-size: 1.2em; color: #333; margin-top:0; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom:10px;">توزیع کارت‌ها در جعبه‌های لایتنر (کل سیستم):</h4>
    <?php if (!empty($stats['box_distribution'])): ?>
        <ul style="list-style-type: none; padding:0; column-count: 2; column-gap: 20px;">
        <?php foreach ($stats['box_distribution'] as $boxName => $count): ?>
            <li style="margin-bottom: 8px; background-color: #f9f9f9; padding: 8px; border-radius:4px; border-left: 3px solid #3498db;">
                <?php echo htmlspecialchars($boxName); ?>:
                <strong><?php echo htmlspecialchars($count); ?> کارت</strong>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>هنوز اطلاعاتی برای توزیع کارت‌ها موجود نیست.</p>
    <?php endif; ?>
</div>

<p style="margin-top: 30px;">از منوی بالا می‌توانید به بخش‌های مختلف مدیریت دسترسی پیدا کنید.</p>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
