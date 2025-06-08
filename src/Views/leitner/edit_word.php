<?php
// $word, $error passed from controller
require_once __DIR__ . '/../partials/header.php';
?>

<h2>ویرایش کلمه: <?php echo htmlspecialchars($word['german_word'] ?? ''); ?></h2>

<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>

<?php if (isset($word) && !empty($word)): ?>
    <form action="/leitner/edit" method="POST"> {/* enctype not needed */}
        <input type="hidden" name="word_id" value="<?php echo htmlspecialchars($word['id']); ?>">

        <div>
            <label for="german_word">کلمه آلمانی (اجباری):</label>
            <input type="text" id="german_word" name="german_word" value="<?php echo htmlspecialchars($word['german_word'] ?? ''); ?>" required class="form-control">
        </div>
        <div>
            <label for="translation">ترجمه فارسی (اجباری):</label>
            <input type="text" id="translation" name="translation" value="<?php echo htmlspecialchars($word['translation'] ?? ''); ?>" required class="form-control">
        </div>
        <div>
            <label for="persian_phonetic_pronunciation">تلفظ فارسی آلمانی:</label>
            <input type="text" id="persian_phonetic_pronunciation" name="persian_phonetic_pronunciation" value="<?php echo htmlspecialchars($word['persian_phonetic_pronunciation'] ?? ''); ?>" class="form-control">
        </div>
        <div>
            <label for="word_type_and_gender">نوعیت کلمه و جنسیت:</label>
            <input type="text" id="word_type_and_gender" name="word_type_and_gender" value="<?php echo htmlspecialchars($word['word_type_and_gender'] ?? ''); ?>" class="form-control">
        </div>
        <div>
            <label for="word_level">سطح کلمه:</label>
            <input type="text" id="word_level" name="word_level" value="<?php echo htmlspecialchars($word['word_level'] ?? ''); ?>" class="form-control">
        </div>
        <div>
            <label for="example_german">مثال آلمانی:</label>
            <textarea id="example_german" name="example_german" rows="2" class="form-control" style="width:100%;"><?php echo htmlspecialchars($word['example_german'] ?? ''); ?></textarea>
        </div>
        <div>
            <label for="example_persian_translation">ترجمه مثال فارسی:</label>
            <textarea id="example_persian_translation" name="example_persian_translation" rows="2" class="form-control" style="width:100%;"><?php echo htmlspecialchars($word['example_persian_translation'] ?? ''); ?></textarea>
        </div>
        <div>
            <label for="audio_url">لینک فایل صوتی (URL):</label>
            <input type="url" id="audio_url" name="audio_url" value="<?php echo htmlspecialchars($word['audio_url'] ?? ''); ?>" placeholder="https://example.com/audio.mp3" class="form-control" style="width:100%;">
            <?php if (!empty($word['audio_url'])): ?>
                <div style="margin-top:10px;">
                    <p style="font-size:0.9em; margin-bottom:5px;">پیش‌نمایش صدای فعلی:</p>
                    <audio controls controlsList="nodownload noremoteplayback" src="<?php echo htmlspecialchars($word['audio_url']); ?>" style="vertical-align: middle; height: 40px; max-width:300px;">
                        مرورگر شما از پخش صوت پشتیبانی نمی‌کند.
                        <a href="<?php echo htmlspecialchars($word['audio_url']); ?>" target="_blank" rel="noopener noreferrer">لینک مستقیم صدا</a>
                    </audio>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="button" style="margin-top:20px;">ذخیره تغییرات</button>
    </form>
<?php else: ?>
    <p>کلمه مورد نظر یافت نشد.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="/leitner/vocabulary" class="button">بازگشت به لیست واژگان</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
