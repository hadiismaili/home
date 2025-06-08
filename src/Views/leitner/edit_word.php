<?php
// Passed from LeitnerController: $word, $error
require_once __DIR__ . '/../partials/header.php';
?>

<h2>ویرایش کلمه</h2>

<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>

<?php if (isset($word) && !empty($word)): ?>
    <form action="/leitner/edit" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
        <div>
            <label for="german_word">کلمه آلمانی:</label>
            <input type="text" id="german_word" name="german_word" value="<?php echo htmlspecialchars($word['german_word']); ?>" required>
        </div>
        <div>
            <label for="translation">ترجمه فارسی:</label>
            <input type="text" id="translation" name="translation" value="<?php echo htmlspecialchars($word['translation']); ?>" required>
        </div>

        <?php if (!empty($word['audio_filename'])): ?>
        <div>
            <p style="margin-top: 10px;">فایل صوتی فعلی: <?php echo htmlspecialchars($word['audio_filename']); ?></p>
            <audio controls src="/audio/<?php echo htmlspecialchars($word['audio_filename']); ?>" style="max-width: 100%;"></audio>
            <input type="hidden" name="existing_audio_filename" value="<?php echo htmlspecialchars($word['audio_filename']); ?>">
        </div>
        <?php endif; ?>
        <div style="margin-top: 10px;">
            <label for="audio_file">بارگذاری فایل صوتی جدید (اختیاری، جایگزین فایل فعلی می‌شود، MP3, OGG, WAV, MP4, AAC, حداکثر 5MB):</label>
            <input type="file" id="audio_file" name="audio_file" accept=".mp3,.ogg,.wav,.mp4,.aac">
        </div>

        <button type="submit" class="button" style="margin-top: 20px;">ذخیره تغییرات</button>
    </form>
<?php else: ?>
    <p>کلمه مورد نظر یافت نشد.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="/leitner/vocabulary" class="button">بازگشت به لیست واژگان</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
