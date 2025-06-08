<?php
require_once __DIR__ . '/../partials/header.php';
// Variables passed from LearningSetController:
// $set (array|null), $formAction (string), $formTitle (string), $submitButtonText (string)
// $assignedWordIds (array, IDs of words currently in the set for edit, or from session on error)
// $availableWords (array, all global words for selection)
// $error (string|null, for displaying validation errors from session)

$assignedWordIds = $assignedWordIds ?? []; // Ensure it's an array if not set (e.g. for add form initial load)
$error = $error ?? ($_SESSION['flash_error'] ?? null); unset($_SESSION['flash_error']); // Check direct var or session
$oldInputName = $set['name'] ?? ($_SESSION['old_input_set']['name'] ?? ''); unset($_SESSION['old_input_set']['name']);
$oldInputDescription = $set['description'] ?? ($_SESSION['old_input_set']['description'] ?? ''); unset($_SESSION['old_input_set']['description']);
// Clear the whole old_input_set if it existed
if (isset($_SESSION['old_input_set']) && empty($_SESSION['old_input_set'])) { unset($_SESSION['old_input_set']); }

?>

<h2><?php echo htmlspecialchars($formTitle); ?></h2>

<?php if (!empty($error)): ?>
    <p style="color:red; border: 1px solid red; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php
// Display general success message if redirected from controller with it (e.g. after update if not redirecting to list)
$successMessage = $_SESSION['flash_message'] ?? null; unset($_SESSION['flash_message']);
if (!empty($successMessage)):
?>
    <p style="color:green; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius:4px;"><?php echo htmlspecialchars($successMessage); ?></p>
<?php endif; ?>


<form action="<?php echo htmlspecialchars($formAction); ?>" method="POST" style="max-width: 800px; margin:auto; padding:20px; border:1px solid #ddd; border-radius:5px; background:#f9f9f9;">
    <?php if (isset($set['id'])): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($set['id']); ?>">
    <?php endif; ?>

    <div style="margin-bottom:15px;">
        <label for="name" style="display:block; margin-bottom:5px; font-weight:bold;">نام مجموعه (اجباری):</label>
        <input type="text" id="name" name="name" class="form-control" style="width:98%; padding:10px; border:1px solid #ccc; border-radius:4px;"
               value="<?php echo htmlspecialchars($oldInputName); ?>" required>
    </div>
    <div style="margin-bottom:20px;">
        <label for="description" style="display:block; margin-bottom:5px;">توضیحات:</label>
        <textarea id="description" name="description" rows="4" class="form-control"
                  style="width:98%; padding:10px; border:1px solid #ccc; border-radius:4px;"><?php echo htmlspecialchars($oldInputDescription); ?></textarea>
    </div>

    <div style="margin-bottom:20px;">
        <label for="word_ids" style="display:block; margin-bottom:5px; font-weight:bold;">کلمات موجود در این مجموعه:</label>
        <p style="font-size:0.85em; color: #555; margin-top:0; margin-bottom:8px;">(از بانک جهانی کلمات انتخاب کنید. برای انتخاب چندگانه از Ctrl/Cmd + کلیک استفاده کنید یا جستجو کنید.)</p>
        <select name="word_ids[]" id="word_ids" multiple="multiple" size="15" class="form-control select2-field" style="width: 100%;" data-placeholder="کلمات را برای افزودن به مجموعه انتخاب کنید...">
            <?php if (!empty($availableWords)): ?>
                <?php foreach ($availableWords as $globalWord): ?>
                    <option value="<?php echo $globalWord['id']; ?>"
                        <?php echo in_array($globalWord['id'], $assignedWordIds) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($globalWord['german_word'] . " (" . ($globalWord['word_level'] ?? '-') . " | " . $globalWord['translation'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option disabled>هیچ کلمه‌ای در بانک جهانی یافت نشد. ابتدا کلمات را از بخش "بانک جهانی کلمات" اضافه کنید.</option>
            <?php endif; ?>
        </select>
    </div>

    <div style="margin-top: 25px;">
        <button type="submit" class="button"><?php echo htmlspecialchars($submitButtonText); ?></button>
        <a href="/admin/learning-sets" class="button" style="background-color: #7f8c8d; margin-right:10px;">انصراف</a>
    </div>
</form>

<!-- Select2 CSS and JS - ensure these are loaded, ideally in a global layout or specific asset management -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> <!-- Ensure jQuery is loaded first -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#word_ids').select2({
            placeholder: "کلمات را انتخاب کنید...",
            allowClear: true,
            dir: "rtl", // For RTL support in Select2
            width: 'resolve', // or '100%'
            theme: "classic" // Optional theme
        });
    });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
