<?php
require_once __DIR__ . '/../partials/header.php';
// Error/message display is already handled by the header partial or included logic
?>

<h2>افزودن کلمه جدید به جعبه لایتنر</h2>

<?php if (!empty($_GET['error'])): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></p>
<?php endif; ?>
<?php if (!empty($_GET['message'])): ?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
<?php endif; ?>

<form action="/leitner/add" method="POST"> {/* enctype not needed for text fields and URL input only */}
    <div>
        <label for="german_word">کلمه آلمانی (اجباری):</label>
        <input type="text" id="german_word" name="german_word" required autofocus class="form-control">
    </div>
    <div>
        <label for="translation">ترجمه فارسی (اجباری):</label>
        <input type="text" id="translation" name="translation" required class="form-control">
    </div>
    <div>
        <label for="persian_phonetic_pronunciation">تلفظ فارسی آلمانی:</label>
        <input type="text" id="persian_phonetic_pronunciation" name="persian_phonetic_pronunciation" class="form-control">
    </div>
    <div>
        <label for="word_type_and_gender">نوعیت کلمه و جنسیت (مثال: اسم مذکر، فعل):</label>
        <input type="text" id="word_type_and_gender" name="word_type_and_gender" class="form-control">
    </div>
    <div>
        <label for="word_level">سطح کلمه (مثال: A1, B2):</label>
        <input type="text" id="word_level" name="word_level" class="form-control">
    </div>
    <div>
        <label for="example_german">مثال آلمانی:</label>
        <textarea id="example_german" name="example_german" rows="2" class="form-control" style="width:100%;"></textarea>
    </div>
    <div>
        <label for="example_persian_translation">ترجمه مثال فارسی:</label>
        <textarea id="example_persian_translation" name="example_persian_translation" rows="2" class="form-control" style="width:100%;"></textarea>
    </div>
    <div>
        <label for="audio_url">لینک فایل صوتی (URL مستقیم به فایل صوتی):</label>
        <input type="url" id="audio_url" name="audio_url" placeholder="https://example.com/audio.mp3" class="form-control" style="width:100%;">
    </div>

    <button type="submit" class="button" style="margin-top:15px;">افزودن کلمه</button>
</form>
<p style="margin-top:15px;"><a href="/leitner/dashboard" class="button">بازگشت به داشبورد</a></p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
