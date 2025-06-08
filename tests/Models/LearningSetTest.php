<?php
namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\LearningSet;
use App\Models\GlobalWord;
use App\Models\User;
use App\Core\Database;
use PDO;

class LearningSetTest extends TestCase
{
    private ?PDO $pdo = null;
    private LearningSet $learningSetModel;
    private GlobalWord $globalWordModel;
    private User $userModel;
    private int $testAdminId;
    private int $wordId1;
    private int $wordId2;

    protected function setUp(): void
    {
        Database::resetInstance();
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->assertNotNull($this->pdo, "PDO connection must be established in setUp.");

        $this->pdo->exec("DELETE FROM learning_set_words");
        $this->pdo->exec("DELETE FROM user_leitner_progress"); // Depends on learning_sets and global_word_bank
        $this->pdo->exec("DELETE FROM learning_sets");
        $this->pdo->exec("DELETE FROM global_word_bank");
        $this->pdo->exec("DELETE FROM users");

        $this->learningSetModel = new LearningSet();
        $this->globalWordModel = new GlobalWord();
        $this->userModel = new User();

        $createAdminResult = $this->userModel->create('admin_set_test', 'adminset@example.com', 'password', true);
        $this->assertTrue($createAdminResult, "setUp: Failed to create admin user.");
        $admin = $this->userModel->findByUsername('admin_set_test');
        $this->assertNotNull($admin, "setUp: Failed to find admin user after creation.");
        $this->testAdminId = $admin['id'];

        // Create some global words
        $this->wordId1 = $this->globalWordModel->create([
            'german_word' => 'Wasser', 'translation' => 'آب', 'persian_phonetic_pronunciation' => null,
            'word_type' => null, 'word_gender' => null, 'word_level' => null,
            'example_german' => null, 'example_persian_translation' => null, 'audio_url' => null
        ]);
        $this->wordId2 = $this->globalWordModel->create([
            'german_word' => 'Brot', 'translation' => 'نان', 'persian_phonetic_pronunciation' => null,
            'word_type' => null, 'word_gender' => null, 'word_level' => null,
            'example_german' => null, 'example_persian_translation' => null, 'audio_url' => null
        ]);
        $this->assertNotFalse($this->wordId1, "setUp: Failed to create wordId1.");
        $this->assertNotFalse($this->wordId2, "setUp: Failed to create wordId2.");
    }

    public function testCreateLearningSet()
    {
        $setId = $this->learningSetModel->create('A1 Nouns', 'Basic A1 Nouns', $this->testAdminId);
        $this->assertNotFalse($setId, "LearningSet creation should return an ID.");
        $this->assertIsInt($setId);

        $set = $this->learningSetModel->findById($setId);
        $this->assertNotNull($set, "Created LearningSet should be findable.");
        $this->assertEquals('A1 Nouns', $set['name']);
        $this->assertEquals($this->testAdminId, $set['admin_id']);
        $this->assertEquals('admin_set_test', $set['admin_username']); // Test JOIN in findById
    }

    public function testFindLearningSetByName()
    {
        $this->learningSetModel->create('A1 Verbs', 'A1 Verbs List', $this->testAdminId);
        $set = $this->learningSetModel->findByName('A1 Verbs');
        $this->assertNotNull($set);
        $this->assertEquals('A1 Verbs List', $set['description']);
    }

    public function testUpdateLearningSet()
    {
        $setId = $this->learningSetModel->create('B1 Adjectives', null, $this->testAdminId);
        $this->assertNotFalse($setId);
        $result = $this->learningSetModel->update($setId, 'B1 Adjectives (Updated)', 'Common adjectives for B1 level');
        $this->assertTrue($result, "Update should return true.");

        $updatedSet = $this->learningSetModel->findById($setId);
        $this->assertNotNull($updatedSet);
        $this->assertEquals('B1 Adjectives (Updated)', $updatedSet['name']);
        $this->assertEquals('Common adjectives for B1 level', $updatedSet['description']);
    }

    public function testDeleteLearningSet()
    {
        $setId = $this->learningSetModel->create('C1 Mixed', 'Advanced words', $this->testAdminId);
        $this->assertNotFalse($setId);
        $this->learningSetModel->addWordToSet($setId, $this->wordId1);

        $this->assertEquals(1, $this->learningSetModel->countWordsInSet($setId));

        $result = $this->learningSetModel->delete($setId);
        $this->assertTrue($result);
        $this->assertNull($this->learningSetModel->findById($setId));

        // Verify cascade on learning_set_words
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM learning_set_words WHERE learning_set_id = :set_id");
        $stmt->bindParam(':set_id', $setId, PDO::PARAM_INT);
        $stmt->execute();
        $this->assertEquals(0, (int)$stmt->fetchColumn(), "Words in set should be deleted by cascade.");
    }

    public function testAddAndRemoveWordsFromSet()
    {
        $setId = $this->learningSetModel->create('Test Set Words', null, $this->testAdminId);
        $this->assertNotFalse($setId);

        $this->assertTrue($this->learningSetModel->addWordToSet($setId, $this->wordId1));
        $this->assertTrue($this->learningSetModel->addWordToSet($setId, $this->wordId2));
        $this->assertEquals(2, $this->learningSetModel->countWordsInSet($setId));

        $this->assertTrue($this->learningSetModel->addWordToSet($setId, $this->wordId1), "Adding same word again should still return true (or not error).");
        $this->assertEquals(2, $this->learningSetModel->countWordsInSet($setId), "Count should not change when adding existing word.");

        $words = $this->learningSetModel->getWordsInSet($setId);
        $this->assertCount(2, $words);
        $wordIdsInSet = array_map(fn($w) => (int)$w['id'], $words); // Cast to int for comparison
        $this->assertContains($this->wordId1, $wordIdsInSet);
        $this->assertContains($this->wordId2, $wordIdsInSet);

        $this->assertTrue($this->learningSetModel->removeWordFromSet($setId, $this->wordId1));
        $this->assertEquals(1, $this->learningSetModel->countWordsInSet($setId));
        $this->assertFalse($this->learningSetModel->removeWordFromSet($setId, $this->wordId1), "Removing already removed word should return false.");
    }

    public function testGetAllLearningSetsAndCount()
    {
        $this->assertEquals(0, $this->learningSetModel->countAll());
        $s1Id = $this->learningSetModel->create('Set Alpha', null, $this->testAdminId);
        $this->assertNotFalse($s1Id);
        $s2Id = $this->learningSetModel->create('Set Beta', null, $this->testAdminId);
        $this->assertNotFalse($s2Id);
        $this->learningSetModel->addWordToSet($s1Id, $this->wordId1);

        $this->assertEquals(2, $this->learningSetModel->countAll());
        $sets = $this->learningSetModel->getAll('name', 'ASC');
        $this->assertCount(2, $sets);
        $this->assertEquals('Set Alpha', $sets[0]['name']);
        $this->assertEquals(1, (int)$sets[0]['word_count']);
        $this->assertEquals('Set Beta', $sets[1]['name']);
        $this->assertEquals(0, (int)$sets[1]['word_count']);
        $this->assertEquals('admin_set_test', $sets[0]['admin_username']);
    }

    protected function tearDown(): void
    {
        Database::resetInstance();
        $this->pdo = null;
    }
}
