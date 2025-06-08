<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Word;
use App\Models\LeitnerCard;
use App\Core\Database;
use PDO;
use DateTime;

class LeitnerCardTest extends TestCase
{
    private ?PDO $pdo = null;
    private User $userModel;
    private Word $wordModel;
    private LeitnerCard $leitnerCardModel;
    private int $testUserId;
    private int $testWordId;

    protected function setUp(): void
    {
        Database::resetInstance();
        $db = new Database();
        $this->pdo = $db->getConnection();

        $this->pdo->exec("DELETE FROM leitner_cards");
        $this->pdo->exec("DELETE FROM words");
        $this->pdo->exec("DELETE FROM users");

        $this->userModel = new User();
        $this->wordModel = new Word();
        $this->leitnerCardModel = new LeitnerCard();

        $this->userModel->create('testuser_card', 'cardtest@example.com', 'password');
        $user = $this->userModel->findByUsername('testuser_card');
        if (!$user) {
            $this->fail("Failed to create test user in setUp.");
        }
        $this->testUserId = $user['id'];

        $wordId = $this->wordModel->create($this->testUserId, 'TestWort', 'TestWord');
        if (!$wordId) {
            $this->fail("Failed to create test word in setUp.");
        }
        $this->testWordId = $wordId;

        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testCreateLeitnerCard()
    {
        $cardId = $this->leitnerCardModel->create($this->testWordId, $this->testUserId);
        $this->assertNotFalse($cardId, "LeitnerCard creation should return an ID (not false).");
        $this->assertIsInt($cardId, "Returned card ID should be an integer.");

        // Test with getCardById first
        $cardFromGetById = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($cardFromGetById, "Created card should be findable by its own ID.");
        $this->assertEquals($this->testWordId, $cardFromGetById['word_id'], "Card retrieved by ID should have correct word_id.");

        // Then test with findByWordId
        $cardFromFindByWordId = $this->leitnerCardModel->findByWordId($this->testWordId, $this->testUserId);
        $this->assertNotNull($cardFromFindByWordId, "Created card should be findable by word_id.");
        $this->assertEquals($cardId, $cardFromFindByWordId['id'], "Card found by word_id should have the same card ID.");

        $this->assertEquals(1, $cardFromFindByWordId['box_number'], "New card should default to box 1.");
        $this->assertNotNull($cardFromFindByWordId['next_review_at'], "New card should have a next_review_at date.");

        $expectedNextReview = (new DateTime())->modify('+'.LeitnerCard::BOX_INTERVALS[1].' day');
        $actualNextReview = new DateTime($cardFromFindByWordId['next_review_at']);
        $this->assertEquals($expectedNextReview->format('Y-m-d'), $actualNextReview->format('Y-m-d'), "Next review date should be ~1 day from now for box 1.");
    }

    public function testWordDeletionCascadesToLeitnerCard()
    {
        $cardId = $this->leitnerCardModel->create($this->testWordId, $this->testUserId);
        $this->assertNotFalse($cardId, "Card creation failed in cascade test setup.");
        $card = $this->leitnerCardModel->getCardById($cardId, $this->testUserId); // Use getCardById
        $this->assertNotNull($card, "Card should exist before word deletion.");

        $deleteResult = $this->wordModel->delete($this->testWordId, $this->testUserId);
        $this->assertTrue($deleteResult, "Word deletion failed.");

        $cardAfterDelete = $this->leitnerCardModel->getCardById($cardId, $this->testUserId); // Check by card ID
        $this->assertNull($cardAfterDelete, "Card should be null after word deletion due to CASCADE.");
    }

    public function testProcessReviewCorrect()
    {
        $cardId = $this->leitnerCardModel->create($this->testWordId, $this->testUserId, 1);
        $this->assertNotFalse($cardId, "Failed to create card for testProcessReviewCorrect.");

        $result = $this->leitnerCardModel->processReview($cardId, $this->testUserId, true);
        $this->assertTrue($result, "processReview should return true on success.");

        $updatedCard = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($updatedCard, "Updated card should be retrievable.");
        $this->assertEquals(2, $updatedCard['box_number'], "Card should move to box 2 after correct review from box 1.");
        $this->assertNotNull($updatedCard['last_reviewed_at'], "last_reviewed_at should be set.");

        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+'.LeitnerCard::BOX_INTERVALS[2].' days');
        $actualNextReview = new DateTime($updatedCard['next_review_at']);
        $this->assertEquals($expectedNextReview->format('Y-m-d'), $actualNextReview->format('Y-m-d'));
    }

    public function testProcessReviewIncorrect()
    {
        $cardId = $this->leitnerCardModel->create($this->testWordId, $this->testUserId, 3);
        $this->assertNotFalse($cardId, "Failed to create card for testProcessReviewIncorrect.");

        $result = $this->leitnerCardModel->processReview($cardId, $this->testUserId, false);
        $this->assertTrue($result);

        $updatedCard = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($updatedCard);
        $this->assertEquals(1, $updatedCard['box_number']);

        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+'.LeitnerCard::BOX_INTERVALS[1].' days');
        $actualNextReview = new DateTime($updatedCard['next_review_at']);
        $this->assertEquals($expectedNextReview->format('Y-m-d'), $actualNextReview->format('Y-m-d'));
    }

    public function testProcessReviewCorrectToMastery()
    {
        $maxBox = LeitnerCard::MAX_BOX_NUMBER;
        $cardId = $this->leitnerCardModel->create($this->testWordId, $this->testUserId, $maxBox);
        $this->assertNotFalse($cardId, "Failed to create card for testProcessReviewCorrectToMastery.");

        $result = $this->leitnerCardModel->processReview($cardId, $this->testUserId, true);
        $this->assertTrue($result);

        $updatedCard = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($updatedCard);
        $this->assertEquals($maxBox + 1, $updatedCard['box_number']);

        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+100 years');
        $actualNextReview = new DateTime($updatedCard['next_review_at']);
        $this->assertEquals($expectedNextReview->format('Y-m-d'), $actualNextReview->format('Y-m-d'));
    }

    public function testGetDueCards()
    {
        $wordId1 = $this->wordModel->create($this->testUserId, 'DueWort1', 'DueWord1');
        $cardId1 = $this->leitnerCardModel->create($wordId1, $this->testUserId);
        $this->assertNotFalse($cardId1);
        $this->pdo->exec("UPDATE leitner_cards SET next_review_at = datetime('now', '-1 day') WHERE id = {$cardId1}");

        $wordId2 = $this->wordModel->create($this->testUserId, 'FutureWort', 'FutureWord');
        $cardId2 = $this->leitnerCardModel->create($wordId2, $this->testUserId);
        $this->assertNotFalse($cardId2);
        $this->pdo->exec("UPDATE leitner_cards SET next_review_at = datetime('now', '+5 days') WHERE id = {$cardId2}");

        $wordId3 = $this->wordModel->create($this->testUserId, 'MasterWort', 'MasterWord');
        $cardId3 = $this->leitnerCardModel->create($wordId3, $this->testUserId, LeitnerCard::MAX_BOX_NUMBER + 1);
        $this->assertNotFalse($cardId3);
        $this->pdo->exec("UPDATE leitner_cards SET next_review_at = datetime('now', '-10 days') WHERE id = {$cardId3}");

        $dueCards = $this->leitnerCardModel->getDueCards($this->testUserId);
        $this->assertCount(1, $dueCards, "Expected only one card to be due.");
        if (count($dueCards) == 1) {
            $this->assertEquals($wordId1, $dueCards[0]['word_id']);
        }
    }

    public function testGetCardStats()
    {
        $w1 = $this->wordModel->create($this->testUserId, 'W1B1', 'T1'); $c1Id = $this->leitnerCardModel->create($w1, $this->testUserId, 1); $this->assertNotFalse($c1Id);
        $w2 = $this->wordModel->create($this->testUserId, 'W2B1', 'T2'); $this->leitnerCardModel->create($w2, $this->testUserId, 1);
        $w3 = $this->wordModel->create($this->testUserId, 'W1B3', 'T3'); $this->leitnerCardModel->create($w3, $this->testUserId, 3);
        $w4 = $this->wordModel->create($this->testUserId, 'W1M', 'T4'); $this->leitnerCardModel->create($w4, $this->testUserId, LeitnerCard::MAX_BOX_NUMBER + 1);

        $this->pdo->exec("UPDATE leitner_cards SET next_review_at = date('now', '-1 day') WHERE id = {$c1Id}");

        $stats = $this->leitnerCardModel->getCardStats($this->testUserId);

        $this->assertEquals(2, $stats[1] ?? 0, "Box 1 count mismatch");
        $this->assertEquals(0, $stats[2] ?? 0, "Box 2 count mismatch");
        $this->assertEquals(1, $stats[3] ?? 0, "Box 3 count mismatch");
        $this->assertEquals(0, $stats[LeitnerCard::MAX_BOX_NUMBER] ?? 0, "Max Box count mismatch"); // Box 5
        $this->assertEquals(1, $stats[LeitnerCard::MAX_BOX_NUMBER + 1] ?? 0, "Mastered Box (Box 6) count mismatch");
        $this->assertEquals(1, $stats['due'] ?? 0, "Due cards count mismatch");
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
