<?php
require_once __DIR__ . '/../partials/header.php';
// Passed from LeitnerController:
// $currentUsername, $availableSets, $activeLearningSet, $activeLearningSetId,
// $stats (for active set), $isReviewAvailable
// $message, $error, $warning (flash messages from session)
?>

<div style="padding: 10px 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

    <h3 style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-top:0;">
        سلام <?php echo htmlspecialchars($currentUsername ?? 'کاربر'); ?>، خوش آمدید!
    </h3>

    <?php if (!empty($message)): ?>
        <p style="color:green; border: 1px solid #a3d9a5; background-color: #e9f5e9; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p style="color:red; border: 1px solid #f5c6cb; background-color: #f8d7da; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (!empty($warning)): ?>
        <p style="color:#856404; border: 1px solid #ffeeba; background-color: #fff3cd; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($warning); ?></p>
    <?php endif; ?>

    <div class="learning-set-selection" style="margin-bottom: 30px; padding:20px; border: 1px solid #eee; border-radius:5px; background-color:#f9f9f9;">
        <h4 style="margin-top:0; margin-bottom:10px;">انتخاب مجموعه آموزشی برای مرور:</h4>
        <?php if (!empty($availableSets)): ?>
            <form action="/leitner/activate-set" method="POST" style="display:flex; align-items:center; gap:10px;">
                <select name="learning_set_id" style="padding: 10px; min-width: 280px; border:1px solid #ccc; border-radius:4px; flex-grow:1;">
                    <option value="">-- یک مجموعه را انتخاب کنید --</option>
                    <?php foreach ($availableSets as $set): ?>
                        <option value="<?php echo $set['id']; ?>" <?php echo (isset($activeLearningSetId) && $activeLearningSetId == $set['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($set['name']); ?> (<?php echo htmlspecialchars($set['word_count'] ?? 0); ?> کلمه)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button" style="padding:10px 15px; white-space:nowrap;">فعال‌سازی و شروع</button>
            </form>
        <?php else: ?>
            <p>در حال حاضر هیچ مجموعه آموزشی توسط مدیر سیستم تعریف نشده است. لطفا بعدا مراجعه کنید.</p>
        <?php endif; ?>
    </div>


    <?php if (isset($activeLearningSet) && $activeLearningSet): ?>
        <div class="active-set-info" style="padding:20px; background-color:#eaf4ff; border:1px solid #cce0ff; border-radius:5px;">
            <h4 style="margin-top:0; margin-bottom:5px;">مجموعه فعال شما: <strong style="color:#0056b3;"><?php echo htmlspecialchars($activeLearningSet['name']); ?></strong></h4>
            <?php if(!empty($activeLearningSet['description'])): ?>
                <p style="font-size:0.9em; margin-bottom:15px;"><?php echo nl2br(htmlspecialchars($activeLearningSet['description'])); ?></p>
            <?php endif; ?>

            <h5 style="margin-bottom:8px; margin-top:20px;">وضعیت جعبه‌های شما برای این مجموعه:</h5>
            <?php if (isset($stats) && !empty($stats)): ?>
                <ul style="list-style-type: none; padding-left: 0; line-height: 1.8;">
                    <li>جعبه آشنایی (Box 0): <strong><?php echo htmlspecialchars($stats[0] ?? 0); ?></strong> کارت</li>
                    <?php for ($i = 1; $i <= App\Models\UserProgressService::MAX_BOX_NUMBER; $i++): ?>
                        <li>جعبه <?php echo $i; ?>: <strong><?php echo htmlspecialchars($stats[$i] ?? 0); ?></strong> کارت</li>
                    <?php endfor; ?>
                    <li>تکمیل شده (Mastered): <strong><?php echo htmlspecialchars($stats[App\Models\UserProgressService::MAX_BOX_NUMBER + 1] ?? 0); ?></strong> کارت</li>
                    <li style="margin-top:15px; padding-top:10px; border-top: 1px solid #cce0ff;">
                        <strong style="color: #c0392b;">کارت‌های آماده مرور در این مجموعه: <?php echo htmlspecialchars($stats['due'] ?? 0); ?></strong>
                    </li>
                </ul>
            <?php else: ?>
                <p>اطلاعات آماری برای این مجموعه وجود ندارد (ممکن است تازه فعال شده باشد یا کلمه‌ای در آن نباشد).</p>
            <?php endif; ?>

            <div style="margin-top:25px;">
                <?php if (isset($isReviewAvailable) && $isReviewAvailable): ?>
                    <a href="/leitner/review" class="button review-button" style="font-size:1.1em; padding:12px 20px;">شروع مرور (<?php echo htmlspecialchars($stats['due'] ?? 0); ?> کارت)</a>
                <?php else: ?>
                    <p style="display:inline-block; margin-right:10px; color: #555; background-color:#fff3cd; border:1px solid #ffeeba; padding:10px; border-radius:4px;">در حال حاضر کارتی برای مرور در این مجموعه آماده نیست.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <p style="padding:15px; background-color:#fff3cd; border:1px solid #ffeeba; border-radius:4px;">برای شروع، لطفا یک مجموعه آموزشی را از لیست بالا انتخاب و فعال کنید.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
