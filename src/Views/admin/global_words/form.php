<?php
// Passed from GlobalWordController:
// $word (null for add, array for edit)
// $formAction (string URL)
// $formTitle (string)
// $submitButtonText (string)
// $error (string, optional for displaying validation errors from session)
// $oldInput (array, optional for repopulating form from session)
require_once __DIR__ . '/../partials/header.php';
?>

<h2><?php echo htmlspecialchars($formTitle); ?></h2>

<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($formAction); ?>" method="POST" style="max-width: 750px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
    <?php if (isset($word['id'])): // Hidden ID for edit mode ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($word['id']); ?>">
    <?php endif; ?>

    <div style="margin-bottom: 15px;">
        <label for="german_word" style="display: block; margin-bottom: 5px; font-weight: bold;">کلمه آلمانی (اجباری):</label>
        <input type="text" id="german_word" name="german_word" class="form-control" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
               value="<?php echo htmlspecialchars($oldInput['german_word'] ?? $word['german_word'] ?? ''); ?>" required>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="translation" style="display: block; margin-bottom: 5px; font-weight: bold;">ترجمه فارسی (اجباری):</label>
        <input type="text" id="translation" name="translation" class="form-control" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
               value="<?php echo htmlspecialchars($oldInput['translation'] ?? $word['translation'] ?? ''); ?>" required>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="persian_phonetic_pronunciation" style="display: block; margin-bottom: 5px;">تلفظ فارسی آلمانی:</label>
        <input type="text" id="persian_phonetic_pronunciation" name="persian_phonetic_pronunciation" class="form-control" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
               value="<?php echo htmlspecialchars($oldInput['persian_phonetic_pronunciation'] ?? $word['persian_phonetic_pronunciation'] ?? ''); ?>">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="word_type" style="display: block; margin-bottom: 5px;">نوع کلمه (مثال: اسم، فعل، صفت):</label>
        <input type="text" id="word_type" name="word_type" class="form-control" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
               value="<?php echo htmlspecialchars($oldInput['word_type'] ?? $word['word_type'] ?? ''); ?>">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="word_gender" style="display: block; margin-bottom: 5px;">جنسیت (برای اسامی، مثال: مذکر، مونث، خنثی):</label>
        <input type="text" id="word_gender" name="word_gender" class="form-control" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
               value="<?php echo htmlspecialchars($oldInput['word_gender'] ?? $word['word_gender'] ?? ''); ?>">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="word_level" style="display: block; margin-bottom: 5px;">سطح کلمه (مثال: A1, B2, C1):</label>
        <input type="text" id="word_level" name="word_level" class="form-control" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
               value="<?php echo htmlspecialchars($oldInput['word_level'] ?? $word['word_level'] ?? ''); ?>">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="example_german" style="display: block; margin-bottom: 5px;">مثال آلمانی:</label>
        <textarea id="example_german" name="example_german" rows="3" class="form-control"
                  style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($oldInput['example_german'] ?? $word['example_german'] ?? ''); ?></textarea>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="example_persian_translation" style="display: block; margin-bottom: 5px;">ترجمه مثال فارسی:</label>
        <textarea id="example_persian_translation" name="example_persian_translation" rows="3" class="form-control"
                  style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($oldInput['example_persian_translation'] ?? $word['example_persian_translation'] ?? ''); ?></textarea>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="audio_url" style="display: block; margin-bottom: 5px;">لینک فایل صوتی (URL مستقیم به فایل صوتی):</label>
        <input type="url" id="audio_url" name="audio_url" class="form-control"
               value="<?php echo htmlspecialchars($oldInput['audio_url'] ?? $word['audio_url'] ?? ''); ?>"
               placeholder="https://example.com/audio.mp3" style="width: 98%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
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

    <div style="margin-top: 25px;">
        <button type="submit" class="button"><?php echo htmlspecialchars($submitButtonText); ?></button>
        <a href="/admin/global-words" class="button" style="background-color: #7f8c8d; margin-right:10px;">انصراف</a>
    </div>
</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
