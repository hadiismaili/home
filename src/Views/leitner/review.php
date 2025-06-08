<?php
require_once __DIR__ . '/../partials/header.php';
// $currentCard, $error, $message are passed from LeitnerController
// $currentCard now contains 'progress_id' as the ID for the user_leitner_progress record.
// It also contains all fields from global_word_bank.
?>

<h2>مرور کلمات</h2>

<?php if (!empty($error)): /* flash_error from session is now used by controller */ ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if (!empty($message)): /* flash_message from session is now used by controller */ ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if (isset($currentCard) && !empty($currentCard)): ?>
    <div class="review-card" style="padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #f9f9f9; margin-top: 20px; text-align: center;">
        <h3 style="margin-top:0;">کلمه آلمانی:</h3>
        <p class="german-word" style="font-size: 2.8em; margin-bottom: 10px; color: #2c3e50; font-weight: bold;"><?php echo htmlspecialchars($currentCard['german_word']); ?></p>

        <?php if (!empty($currentCard['persian_phonetic_pronunciation'])): ?>
            <p style="font-size: 1.2em; color: #555; margin-bottom: 8px;">(تلفظ: <?php echo htmlspecialchars($currentCard['persian_phonetic_pronunciation']); ?>)</p>
        <?php endif; ?>
        <?php if (!empty($currentCard['word_type_and_gender'])): // This was word_type in global_word_bank, need to check if controller passes it as this key
            // Assuming controller's getDueCardsForSet selects word_type and word_gender separately if needed.
            // For now, if the key exists from the SELECT gwb.*
            ?>
            <p style="font-size: 1.1em; color: #777; margin-bottom: 8px;">نوع/جنسیت: <?php echo htmlspecialchars($currentCard['word_type'] ?? ''); ?> <?php echo htmlspecialchars($currentCard['word_gender'] ?? ''); ?></p>
        <?php endif; ?>
        <?php if (!empty($currentCard['word_level'])): ?>
            <p style="font-size: 1.1em; color: #777; margin-bottom: 25px;">سطح: <?php echo htmlspecialchars($currentCard['word_level']); ?></p>
        <?php endif; ?>

        <button id="showTranslationBtn" class="button" style="padding: 10px 20px; font-size: 1.1em;">نمایش ترجمه و جزئیات</button>

        <div id="translationContainer" style="display:none; margin-top: 20px; border-top: 1px solid #eee; padding-top:20px;">
            <h4 style="font-size:1.4em; color:#333;">ترجمه فارسی:</h4>
            <p class="translation" style="font-size: 2em; color: #27ae60; margin-bottom:20px;"><?php echo htmlspecialchars($currentCard['translation']); ?></p>

            <?php if (!empty($currentCard['example_german'])): ?>
                <div style="margin-top:20px; padding:15px; background-color:#f0f8ff; border-radius:4px; border: 1px solid #e0e0e0;">
                    <p style="margin:5px 0;"><strong>مثال آلمانی:</strong> <?php echo nl2br(htmlspecialchars($currentCard['example_german'])); ?></p>
                    <?php if (!empty($currentCard['example_persian_translation'])): ?>
                        <p style="margin:5px 0;"><strong>ترجمه مثال:</strong> <?php echo nl2br(htmlspecialchars($currentCard['example_persian_translation'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($currentCard['audio_url'])): ?>
                <div style="margin-top:20px;">
                    <p style="font-size:0.9em; margin-bottom:5px;">پخش تلفظ:</p>
                    <audio controls controlsList="nodownload noremoteplayback" src="<?php echo htmlspecialchars($currentCard['audio_url']); ?>" style="width:100%; max-width:350px;">
                        مرورگر شما از پخش صوت پشتیبانی نمی‌کند.
                        <a href="<?php echo htmlspecialchars($currentCard['audio_url']); ?>" target="_blank" rel="noopener noreferrer">لینک مستقیم صدا</a>
                    </audio>
                </div>
            <?php endif; ?>
        </div>

        <form action="/leitner/review/process" method="POST" id="reviewOutcomeForm" style="display:none; margin-top: 30px; padding-top:20px; border-top:1px solid #eee;">
            <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($currentCard['progress_id']); ?>"> {/* Value is progress_id */}
            <p style="font-weight:bold; margin-bottom:15px;">نتیجه مرور شما چطور بود؟</p>
            <button type="submit" name="outcome" value="<?php echo App\Models\UserProgressService::OUTCOME_CORRECT; ?>" class="button" style="background-color: #5cb85c; margin: 5px; padding: 10px 15px; font-size: 1em;">می دانم</button>
            <button type="submit" name="outcome" value="<?php echo App\Models\UserProgressService::OUTCOME_PARTIAL; ?>" class="button" style="background-color: #f0ad4e; margin: 5px; padding: 10px 15px; font-size: 1em;">نسبتا میفهمم</button>
            <button type="submit" name="outcome" value="<?php echo App\Models\UserProgressService::OUTCOME_INCORRECT; ?>" class="button" style="background-color: #d9534f; margin: 5px; padding: 10px 15px; font-size: 1em;">نمی دانم</button>
        </form>
    </div>

    <script>
        document.getElementById('showTranslationBtn').addEventListener('click', function() {
            document.getElementById('translationContainer').style.display = 'block';
            document.getElementById('reviewOutcomeForm').style.display = 'block';
            this.style.display = 'none';
        });
    </script>

<?php else: ?>
    <p>کارت دیگری برای مرور وجود ندارد. می‌توانید بعداً دوباره تلاش کنید یا کلمات جدید اضافه کنید.</p>
    <p><a href="/leitner/dashboard" class="button">بازگشت به داشبورد</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
