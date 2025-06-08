<?php
// Passed from LeitnerController::showReview(): $currentCard, $error, $message
require_once __DIR__ . '/../partials/header.php';
?>

<h2>مرور کلمات</h2>

<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>
<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($message)); ?></p>
<?php endif; ?>

<?php if (isset($currentCard) && !empty($currentCard)): ?>
    <div class="review-card">
        <h3>کلمه آلمانی:</h3>
        <p class="german-word"><?php echo htmlspecialchars($currentCard['german_word']); ?></p>

        <button id="showTranslationBtn" class="button">نمایش ترجمه</button>

        <div id="translationContainer" style="display:none; margin-top: 15px;">
            <h4>ترجمه:</h4>
            <p class="translation"><?php echo htmlspecialchars($currentCard['translation']); ?></p>
            <?php if (!empty($currentCard['audio_filename'])): ?>
                <audio controls src="/audio/<?php echo htmlspecialchars($currentCard['audio_filename']); ?>" style="margin-top:10px;">
                    مرورگر شما از پخش صوت پشتیبانی نمی‌کند.
                </audio>
            <?php endif; ?>
        </div>

        <form action="/leitner/review/process" method="POST" id="reviewOutcomeForm" style="display:none; margin-top: 20px;">
            <input type="hidden" name="card_id" value="<?php echo $currentCard['id']; ?>">
            <p>نتیجه مرور شما چطور بود؟</p>
            <button type="submit" name="outcome" value="<?php echo App\Models\LeitnerCard::OUTCOME_CORRECT; ?>" class="button" style="background-color: #5cb85c; margin-left: 5px; margin-right: 5px;">می دانم</button>
            <button type="submit" name="outcome" value="<?php echo App\Models\LeitnerCard::OUTCOME_PARTIAL; ?>" class="button" style="background-color: #f0ad4e; margin-left: 5px; margin-right: 5px;">نسبتا میفهمم</button>
            <button type="submit" name="outcome" value="<?php echo App\Models\LeitnerCard::OUTCOME_INCORRECT; ?>" class="button" style="background-color: #d9534f; margin-left: 5px; margin-right: 5px;">نمی دانم</button>
        </form>
    </div>

    <script>
        document.getElementById('showTranslationBtn').addEventListener('click', function() {
            document.getElementById('translationContainer').style.display = 'block';
            document.getElementById('reviewOutcomeForm').style.display = 'block';
            this.style.display = 'none'; // Hide the "Show Translation" button
        });
    </script>

<?php else: ?>
    <p>کارت دیگری برای مرور وجود ندارد. می‌توانید بعداً دوباره تلاش کنید یا کلمات جدید اضافه کنید.</p>
    <p><a href="/leitner/dashboard" class="button">بازگشت به داشبورد</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
