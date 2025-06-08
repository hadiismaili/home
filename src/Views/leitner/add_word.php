<?php
// Assuming $error, $message might be set via query parameters
require_once __DIR__ . '/../partials/header.php';
?>

<h2>افزودن کلمه جدید به جعبه لایتنر</h2>

<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($error)); ?></p>
<?php endif; ?>
<?php if (!empty($message)): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px;"><?php echo htmlspecialchars(urldecode($message)); ?></p>
<?php endif; ?>

<form action="/leitner/add" method="POST" enctype="multipart/form-data">
    <div>
        <label for="german_word">کلمه آلمانی:</label>
        <input type="text" id="german_word" name="german_word" required autofocus>
    </div>
    <div>
        <label for="translation">ترجمه فارسی:</label>
        <input type="text" id="translation" name="translation" required>
    </div>
    <div>
        <label for="audio_file">فایل صوتی (اختیاری، MP3, OGG, WAV, MP4, AAC, حداکثر 5MB):</label>
        <input type="file" id="audio_file" name="audio_file" accept=".mp3,.ogg,.wav,.mp4,.aac">
    </div>
    <button type="submit">افزودن کلمه</button>
</form>
<p><a href="/leitner/dashboard">بازگشت به داشبورد</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
