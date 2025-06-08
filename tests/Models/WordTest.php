<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Word;
use App\Core\Database;
use PDO;

class WordTest extends TestCase
{
    private ?PDO $pdo = null;
    private User $userModel;
    private Word $wordModel;
    private int $testUserId;

    protected function setUp(): void
    {
        Database::resetInstance();
        $db = new Database();
        $this->pdo = $db->getConnection();

        $this->pdo->exec("DELETE FROM leitner_cards"); // Clear dependent table first
        $this->pdo->exec("DELETE FROM words");
        $this->pdo->exec("DELETE FROM users");

        $this->userModel = new User();
        $this->wordModel = new Word();

        $createUserResult = $this->userModel->create('word_test_user', 'wordtest@example.com', 'password');
        $this->assertTrue($createUserResult, "setUp: Failed to create test user.");
        $user = $this->userModel->findByUsername('word_test_user');
        if (!$user) {
            $this->fail("Test user for WordTest setup failed: user not found after creation.");
        }
        $this->testUserId = $user['id'];

        if (session_status() == PHP_SESSION_ACTIVE) {
             session_unset();
             session_destroy();
        }
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testCreateWordWithAllFields()
    {
        $data = [
            'user_id' => $this->testUserId,
            'german_word' => 'Der Bus',
            'translation' => 'اتوبوس',
            'persian_phonetic' => 'دِع باس',
            'word_type_gender' => 'اسم مذکر (der)',
            'word_level' => 'A1',
            'example_german' => 'Ich nehme den Bus.',
            'example_persian' => 'من اتوبوس را می گیرم.',
            'audio_url' => 'https://example.com/audio/der_bus.mp3'
        ];

        $wordId = $this->wordModel->create(
            $data['user_id'], $data['german_word'], $data['translation'],
            $data['persian_phonetic'], $data['word_type_gender'], $data['word_level'],
            $data['example_german'], $data['example_persian'], $data['audio_url']
        );

        $this->assertNotFalse($wordId, "Word creation should return an ID that is not false.");
        $this->assertIsInt($wordId, "Word ID should be an integer.");

        $word = $this->wordModel->findById($wordId, $this->testUserId);
        $this->assertNotNull($word, "Word should be found by ID after creation.");
        $this->assertEquals($data['german_word'], $word['german_word']);
        $this->assertEquals($data['translation'], $word['translation']);
        $this->assertEquals($data['persian_phonetic'], $word['persian_phonetic_pronunciation']);
        $this->assertEquals($data['word_type_gender'], $word['word_type_and_gender']);
        $this->assertEquals($data['word_level'], $word['word_level']);
        $this->assertEquals($data['example_german'], $word['example_german']);
        $this->assertEquals($data['example_persian'], $word['example_persian_translation']);
        $this->assertEquals($data['audio_url'], $word['audio_url']);
        $this->assertArrayNotHasKey('audio_filename', $word, "Old 'audio_filename' column should not be present.");
    }

    public function testCreateWordWithOnlyRequiredFields()
    {
        $german = 'Hallo';
        $translation = 'سلام';
        $wordId = $this->wordModel->create($this->testUserId, $german, $translation);

        $this->assertNotFalse($wordId, "Word creation with only required fields should return an ID.");
        $word = $this->wordModel->findById($wordId, $this->testUserId);
        $this->assertNotNull($word, "Word created with only required fields should be findable.");
        $this->assertEquals($german, $word['german_word']);
        $this->assertEquals($translation, $word['translation']);
        $this->assertNull($word['persian_phonetic_pronunciation']);
        $this->assertNull($word['word_type_and_gender']);
        $this->assertNull($word['word_level']);
        $this->assertNull($word['example_german']);
        $this->assertNull($word['example_persian_translation']);
        $this->assertNull($word['audio_url']);
    }


    public function testUpdateWordWithAllFields()
    {
        $wordId = $this->wordModel->create($this->testUserId, 'Alt', 'قدیمی');
        $this->assertNotFalse($wordId, "Initial word creation failed in update test.");

        $updateData = [
            'german_word' => 'Neu Wort',
            'translation' => 'کلمه جدید',
            'persian_phonetic' => 'نُی وُرت',
            'word_type_gender' => 'اسم خنثی (das)',
            'word_level' => 'A2',
            'example_german' => 'Das ist ein neues Wort.',
            'example_persian' => 'این یک کلمه جدید است.',
            'audio_url' => 'https://example.com/audio/neu_wort.mp3'
        ];

        $result = $this->wordModel->update(
            $wordId, $this->testUserId,
            $updateData['german_word'], $updateData['translation'],
            $updateData['persian_phonetic'], $updateData['word_type_gender'], $updateData['word_level'],
            $updateData['example_german'], $updateData['example_persian'], $updateData['audio_url']
        );
        $this->assertTrue($result, "Update should return true for successful update.");

        $updatedWord = $this->wordModel->findById($wordId, $this->testUserId);
        $this->assertNotNull($updatedWord, "Updated word should be retrievable.");
        $this->assertEquals($updateData['german_word'], $updatedWord['german_word']);
        $this->assertEquals($updateData['translation'], $updatedWord['translation']);
        $this->assertEquals($updateData['persian_phonetic'], $updatedWord['persian_phonetic_pronunciation']);
        $this->assertEquals($updateData['word_type_gender'], $updatedWord['word_type_and_gender']);
        $this->assertEquals($updateData['word_level'], $updatedWord['word_level']);
        $this->assertEquals($updateData['example_german'], $updatedWord['example_german']);
        $this->assertEquals($updateData['example_persian'], $updatedWord['example_persian_translation']);
        $this->assertEquals($updateData['audio_url'], $updatedWord['audio_url']);
    }

    public function testUpdateWordToClearOptionalFields()
    {
        $initialData = [
            $this->testUserId, 'Komplett', 'کامل',
            'کُمپلِت', 'صفت', 'B1',
            'Alles ist komplett.', 'همه چیز کامل است.',
            'https://example.com/komplett.mp3'
        ];
        $wordId = $this->wordModel->create(...$initialData);
        $this->assertNotFalse($wordId, "Initial word creation with all fields failed.");

        $result = $this->wordModel->update(
            $wordId, $this->testUserId,
            'Komplett (bearbeitet)', 'کامل (ویرایش شده)',
            null, null, null, null, null, null // Clear all optional fields
        );
        $this->assertTrue($result, "Update to clear optional fields should return true.");
        $updatedWord = $this->wordModel->findById($wordId, $this->testUserId);
        $this->assertNotNull($updatedWord, "Word should be findable after clearing optional fields.");
        $this->assertEquals('Komplett (bearbeitet)', $updatedWord['german_word']);
        $this->assertNull($updatedWord['persian_phonetic_pronunciation']);
        $this->assertNull($updatedWord['word_type_and_gender']);
        $this->assertNull($updatedWord['word_level']);
        $this->assertNull($updatedWord['example_german']);
        $this->assertNull($updatedWord['example_persian_translation']);
        $this->assertNull($updatedWord['audio_url']);
    }

    public function testFindByGermanWord()
    {
        $german = 'Tschüss';
        $translation = 'خداحافظ';
        $this->wordModel->create($this->testUserId, $german, $translation, null, null, 'A1');

        $word = $this->wordModel->findByGermanWord($german, $this->testUserId);
        $this->assertNotNull($word, "Word 'Tschüss' should be found.");
        $this->assertEquals($translation, $word['translation']);
        $this->assertEquals('A1', $word['word_level']);

        $this->assertNull($this->wordModel->findByGermanWord('NichtExistent', $this->testUserId));
    }

    public function testGetAllByUser()
    {
        $this->wordModel->create($this->testUserId, 'Apfel', 'سیب', null, null, 'A1');
        usleep(10000);
        $this->wordModel->create($this->testUserId, 'Banane', 'موز', null, null, 'A1');

        $createUserResult = $this->userModel->create('otheruser_gwtest', 'othergw@example.com', 'pass');
        $this->assertTrue($createUserResult);
        $otherUser = $this->userModel->findByUsername('otheruser_gwtest');
        $this->assertNotNull($otherUser);
        $otherUserId = $otherUser['id'];
        $this->wordModel->create($otherUserId, 'Kirsche', 'گیلاس');

        $words = $this->wordModel->getAllByUser($this->testUserId);
        $this->assertCount(2, $words);
        $this->assertEquals('Banane', $words[0]['german_word']);
        $this->assertEquals('Apfel', $words[1]['german_word']);
    }

    public function testDeleteWord()
    {
        $wordId = $this->wordModel->create($this->testUserId, 'Löschen Wort', 'کلمه برای حذف');
        $this->assertNotFalse($wordId);

        $result = $this->wordModel->delete($wordId, $this->testUserId);
        $this->assertTrue($result);
        $this->assertNull($this->wordModel->findById($wordId, $this->testUserId));
    }

    public function testCountWordsByUserId()
    {
        $this->assertEquals(0, $this->wordModel->countWordsByUserId($this->testUserId));
        $this->wordModel->create($this->testUserId, 'W1', 'T1');
        $this->wordModel->create($this->testUserId, 'W2', 'T2');
        $this->assertEquals(2, $this->wordModel->countWordsByUserId($this->testUserId));
    }

    public function testCountAllWords()
    {
        $this->assertEquals(0, $this->wordModel->countAll());
        $this->wordModel->create($this->testUserId, 'W1Total', 'T1Total');

        $createUserResult = $this->userModel->create('otheruser_cw', 'othercw@example.com', 'pass');
        $this->assertTrue($createUserResult);
        $otherUser = $this->userModel->findByUsername('otheruser_cw');
        $this->assertNotNull($otherUser);
        $otherUserId = $otherUser['id'];
        $this->wordModel->create($otherUserId, 'W2TotalOther', 'T2TotalOther');

        $this->assertEquals(2, $this->wordModel->countAll());
    }

    protected function tearDown(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
        Database::resetInstance();
        $this->pdo = null;
    }
}
