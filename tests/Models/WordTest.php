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

        $this->pdo->exec("DELETE FROM words");
        $this->pdo->exec("DELETE FROM leitner_cards");
        $this->pdo->exec("DELETE FROM users");

        $this->userModel = new User();
        $this->wordModel = new Word();

        $this->userModel->create('testuser_word', 'wordtest@example.com', 'password');
        $user = $this->userModel->findByUsername('testuser_word');
        if (!$user) {
            $this->fail("Failed to create test user in setUp.");
        }
        $this->testUserId = $user['id'];

        // Minimal session handling for safety, though model tests shouldn't rely on it.
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            @session_start(); // Use @ to suppress warnings if headers already sent in other contexts
        }
        $_SESSION = []; // Clear session data at start of test
    }

    public function testCreateWord()
    {
        $german = 'Hallo';
        $translation = 'Hello';
        $wordId = $this->wordModel->create($this->testUserId, $german, $translation, 'hallo.mp3');

        $this->assertNotFalse($wordId, "Word creation should return an ID that is not false.");
        $this->assertIsInt($wordId, "Word ID should be an integer.");

        $word = $this->wordModel->findById($wordId, $this->testUserId);
        $this->assertNotNull($word, "Word should be found by ID after creation.");
        $this->assertEquals($german, $word['german_word']);
        $this->assertEquals($translation, $word['translation']);
        $this->assertEquals('hallo.mp3', $word['audio_filename']);
    }

    public function testFindByGermanWord()
    {
        $german = 'Tschüss';
        $translation = 'Bye';
        $this->wordModel->create($this->testUserId, $german, $translation);

        $word = $this->wordModel->findByGermanWord($german, $this->testUserId);
        $this->assertNotNull($word, "Should find word by German term for the correct user.");
        $this->assertEquals($translation, $word['translation']);

        $this->assertNull($this->wordModel->findByGermanWord('NichtExistent', $this->testUserId), "Should not find non-existent word.");

        $otherUserId = $this->testUserId + 100;
        $this->assertNull($this->wordModel->findByGermanWord($german, $otherUserId), "Should not find word if it belongs to a different user.");
    }

    public function testGetAllByUser()
    {
        $word1German = 'Apfel';
        $this->wordModel->create($this->testUserId, $word1German, 'Apple');
        // Ensure timestamp difference or rely on ID tie-breaker
        usleep(10000); // Sleep for 10ms to ensure different timestamp if system clock resolution is low
        $word2German = 'Banane';
        $this->wordModel->create($this->testUserId, $word2German, 'Banana');

        $this->userModel->create('otheruser_wordtest', 'other_wordtest@example.com', 'pass');
        $otherUser = $this->userModel->findByUsername('otheruser_wordtest');
        if (!$otherUser) {
            $this->fail("Failed to create 'otheruser_wordtest' in testGetAllByUser.");
        }
        $this->wordModel->create($otherUser['id'], 'Kirsche', 'Cherry');

        $words = $this->wordModel->getAllByUser($this->testUserId);
        $this->assertCount(2, $words, "Should retrieve 2 words for the test user.");

        // With ORDER BY created_at DESC, id DESC: 'Banane' (created later) should be first.
        $this->assertEquals($word2German, $words[0]['german_word']);
        $this->assertEquals($word1German, $words[1]['german_word']);
    }

    public function testUpdateWord()
    {
        $wordId = $this->wordModel->create($this->testUserId, 'Alt', 'Old');
        $this->assertNotFalse($wordId, "Initial word creation failed in testUpdateWord.");

        $newGerman = 'Neu';
        $newTranslation = 'New';
        $newAudio = 'neu.mp3';
        $result = $this->wordModel->update($wordId, $this->testUserId, $newGerman, $newTranslation, $newAudio);
        $this->assertTrue($result, "Word update should return true for successful update.");

        $updatedWord = $this->wordModel->findById($wordId, $this->testUserId);
        $this->assertNotNull($updatedWord, "Updated word should be retrievable.");
        $this->assertEquals($newGerman, $updatedWord['german_word']);
        $this->assertEquals($newTranslation, $updatedWord['translation']);
        $this->assertEquals($newAudio, $updatedWord['audio_filename']);

        $otherUserId = $this->testUserId + 100;
        // This should return false as no rows are affected for otherUserId
        $this->assertFalse($this->wordModel->update($wordId, $otherUserId, "No", "No"), "Update should fail for a word not owned by the user.");
    }

    public function testDeleteWord()
    {
        $wordId = $this->wordModel->create($this->testUserId, 'Löschen', 'Delete');
        $this->assertNotFalse($wordId, "Initial word creation failed in testDeleteWord.");

        $result = $this->wordModel->delete($wordId, $this->testUserId);
        $this->assertTrue($result, "Word deletion should return true.");
        $this->assertNull($this->wordModel->findById($wordId, $this->testUserId), "Word should be null after deletion.");

        $this->assertFalse($this->wordModel->delete($wordId, $this->testUserId), "Deleting an already deleted word should return false.");

        $wordId2 = $this->wordModel->create($this->testUserId, 'NoDelete', 'No');
        $this->assertNotFalse($wordId2, "Creation of second word for delete test failed.");
        $otherUserId = $this->testUserId + 100;
        $this->assertFalse($this->wordModel->delete($wordId2, $otherUserId), "Delete should fail for a word not owned by the user.");
        $this->assertNotNull($this->wordModel->findById($wordId2, $this->testUserId), "Word should still exist if delete by wrong user was attempted.");
    }

    protected function tearDown(): void
    {
        if (session_status() != PHP_SESSION_NONE && session_status() == PHP_SESSION_ACTIVE) {
             session_unset();
             session_destroy();
        }
        Database::resetInstance();
        $this->pdo = null;
    }
}
