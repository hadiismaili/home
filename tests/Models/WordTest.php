<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Word; // This is the old Word model, should be GlobalWord
use App\Models\GlobalWord; // Correct model to use
use App\Core\Database;
use PDO;

class WordTest extends TestCase // Should be renamed to GlobalWordTest
{
    private ?PDO $pdo = null;
    private User $userModel;
    private GlobalWord $globalWordModel; // Use GlobalWord model
    private int $testUserId; // Still useful if global words are associated with a creator/admin later

    protected function setUp(): void
    {
        Database::resetInstance();
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->assertNotNull($this->pdo, "PDO connection must be established in setUp.");

        // Clear tables: user_leitner_progress depends on users, global_word_bank, learning_sets
        // learning_set_words depends on learning_sets, global_word_bank
        // users.active_learning_set_id depends on learning_sets
        // learning_sets.admin_id depends on users
        // For GlobalWordTest, primarily concerned with global_word_bank.
        // Order: junction tables, then tables they depend on, then users/learning_sets if interdependent.
        $this->pdo->exec("DELETE FROM user_leitner_progress");
        $this->pdo->exec("DELETE FROM learning_set_words");
        $this->pdo->exec("DELETE FROM global_word_bank");
        // No need to delete users or learning_sets if GlobalWord doesn't directly depend on them for these tests.
        // However, if any test creates users (e.g. for an admin_id if that were part of GlobalWord), clear users.
        // For now, GlobalWord is independent of users table for its own CRUD.

        $this->userModel = new User(); // Keep if any test needs to create a user for context (e.g. admin actions)
        $this->globalWordModel = new GlobalWord(); // Instantiate GlobalWord

        // Create a dummy user if some tests might imply user context, though not strictly for global words.
        $createUserResult = $this->userModel->create('word_test_user', 'wordtest@example.com', 'password');
        $this->assertTrue($createUserResult, "setUp: Failed to create test user for context.");
        $user = $this->userModel->findByUsername('word_test_user');
        $this->assertNotNull($user);
        $this->testUserId = $user['id']; // For context if any test needs it, though GlobalWord doesn't use user_id.


        if (session_status() == PHP_SESSION_ACTIVE) {
             session_unset();
             session_destroy();
        }
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testCreateGlobalWordWithAllFields()
    {
        $data = [
            // user_id is not part of global_word_bank table schema directly
            'german_word' => 'Der Bus',
            'translation' => 'اتوبوس',
            'persian_phonetic_pronunciation' => 'دِع باس',
            'word_type' => 'اسم', // Changed from word_type_and_gender
            'word_gender' => 'مذکر (der)', // Changed from word_type_and_gender
            'word_level' => 'A1',
            'example_german' => 'Ich nehme den Bus.',
            'example_persian_translation' => 'من اتوبوس را می گیرم.',
            'audio_url' => 'https://example.com/audio/der_bus.mp3'
        ];

        $wordId = $this->globalWordModel->create($data);

        $this->assertNotFalse($wordId, "GlobalWord creation should return an ID that is not false.");
        $this->assertIsInt($wordId, "Word ID should be an integer.");

        $word = $this->globalWordModel->findById($wordId);
        $this->assertNotNull($word, "Word should be found by ID after creation.");
        $this->assertEquals($data['german_word'], $word['german_word']);
        $this->assertEquals($data['translation'], $word['translation']);
        $this->assertEquals($data['persian_phonetic_pronunciation'], $word['persian_phonetic_pronunciation']);
        $this->assertEquals($data['word_type'], $word['word_type']);
        $this->assertEquals($data['word_gender'], $word['word_gender']);
        $this->assertEquals($data['word_level'], $word['word_level']);
        $this->assertEquals($data['example_german'], $word['example_german']);
        $this->assertEquals($data['example_persian_translation'], $word['example_persian_translation']);
        $this->assertEquals($data['audio_url'], $word['audio_url']);
    }

    public function testCreateGlobalWordWithOnlyRequiredFields()
    {
        $data = [
            'german_word' => 'Hallo', 'translation' => 'سلام',
            'persian_phonetic_pronunciation' => null, 'word_type' => null, 'word_gender' => null,
            'word_level' => null, 'example_german' => null, 'example_persian_translation' => null,
            'audio_url' => null
        ];
        $wordId = $this->globalWordModel->create($data);

        $this->assertNotFalse($wordId, "Word creation with only required fields should return an ID.");
        $word = $this->globalWordModel->findById($wordId);
        $this->assertNotNull($word, "Word created with only required fields should be findable.");
        $this->assertEquals($data['german_word'], $word['german_word']);
        $this->assertEquals($data['translation'], $word['translation']);
        $this->assertNull($word['persian_phonetic_pronunciation']);
        $this->assertNull($word['audio_url']);
    }

    public function testUpdateGlobalWordWithAllFields()
    {
        $initialData = [
            'german_word' => 'Alt', 'translation' => 'قدیمی',
            'persian_phonetic_pronunciation' => null, 'word_type' => null, 'word_gender' => null,
            'word_level' => 'A1', 'example_german' => null, 'example_persian_translation' => null,
            'audio_url' => null
        ];
        $wordId = $this->globalWordModel->create($initialData);
        $this->assertNotFalse($wordId, "Initial word creation failed in update test.");

        $updateData = [
            'german_word' => 'Neu Wort', 'translation' => 'کلمه جدید',
            'persian_phonetic_pronunciation' => 'نُی وُرت', 'word_type' => 'اسم',
            'word_gender' => 'خنثی (das)', 'word_level' => 'A2',
            'example_german' => 'Das ist ein neues Wort.', 'example_persian_translation' => 'این یک کلمه جدید است.',
            'audio_url' => 'https://example.com/audio/neu_wort.mp3'
        ];

        $result = $this->globalWordModel->update($wordId, $updateData);
        $this->assertTrue($result, "Update should return true for successful update.");

        $updatedWord = $this->globalWordModel->findById($wordId);
        $this->assertNotNull($updatedWord, "Updated word should be retrievable.");
        $this->assertEquals($updateData['german_word'], $updatedWord['german_word']);
        $this->assertEquals($updateData['persian_phonetic_pronunciation'], $updatedWord['persian_phonetic_pronunciation']);
        $this->assertEquals($updateData['word_gender'], $updatedWord['word_gender']);
    }

    public function testUpdateGlobalWordToClearOptionalFields()
    {
        $initialData = [
            'german_word' => 'Komplett', 'translation' => 'کامل',
            'persian_phonetic_pronunciation' => 'کُمپلِت', 'word_type' => 'صفت', 'word_gender' => null,
            'word_level' => 'B1', 'example_german' => 'Alles ist komplett.',
            'example_persian_translation' => 'همه چیز کامل است.', 'audio_url' => 'https://example.com/komplett.mp3'
        ];
        $wordId = $this->globalWordModel->create($initialData);
        $this->assertNotFalse($wordId, "Initial word creation with all fields failed.");

        $updateData = [
            'german_word' => 'Komplett (bearbeitet)', 'translation' => 'کامل (ویرایش شده)',
            'persian_phonetic_pronunciation' => null, 'word_type' => null, 'word_gender' => null,
            'word_level' => null, 'example_german' => null, 'example_persian_translation' => null,
            'audio_url' => null
        ];
        $result = $this->globalWordModel->update($wordId, $updateData);
        $this->assertTrue($result);
        $updatedWord = $this->globalWordModel->findById($wordId);
        $this->assertEquals('Komplett (bearbeitet)', $updatedWord['german_word']);
        $this->assertNull($updatedWord['persian_phonetic_pronunciation']);
        $this->assertNull($updatedWord['word_type']);
        $this->assertNull($updatedWord['word_gender']);
    }

    public function testFindByGermanWord() { /* Kept from previous, should still pass */
        $data = ['german_word' => 'Tschüss','translation' => 'خداحافظ','word_level' => 'A1', /* other fields null */];
        $this->globalWordModel->create($data);
        $word = $this->globalWordModel->findByGermanWord('Tschüss');
        $this->assertNotNull($word);
        $this->assertEquals('خداحافظ', $word['translation']);
        $this->assertEquals('A1', $word['word_level']);
        $this->assertNull($this->globalWordModel->findByGermanWord('NichtExistent'));
    }

    public function testGetAllGlobalWordsAndCount() { /* Kept from previous, should still pass */
        $this->assertEquals(0, $this->globalWordModel->countAll());
        $this->globalWordModel->create(['german_word' => 'Zitrone', 'translation' => 'لیمو', /* other fields null */]);
        $this->globalWordModel->create(['german_word' => 'Orange', 'translation' => 'پرتقال', /* other fields null */]);
        $this->assertEquals(2, $this->globalWordModel->countAll());
        $words = $this->globalWordModel->getAll(10, 0, 'german_word', 'ASC');
        $this->assertCount(2, $words);
        $this->assertEquals('Orange', $words[0]['german_word']);
        $this->assertEquals('Zitrone', $words[1]['german_word']);
    }

    public function testDeleteGlobalWord() { /* Kept from previous, should still pass */
        $wordId = $this->globalWordModel->create(['german_word' => 'LöschenGlobal', 'translation' => 'حذف سراسری', /* other fields null */]);
        $this->assertNotFalse($wordId);
        $result = $this->globalWordModel->delete($wordId);
        $this->assertTrue($result);
        $this->assertNull($this->globalWordModel->findById($wordId));
    }

    // Remove old Word specific tests that relied on user_id if they were here.
    // The countWordsByUserId and countAll are now specific to GlobalWord context (all words, no user filter).

    protected function tearDown(): void
    {
        Database::resetInstance();
        $this->pdo = null;
    }
}
