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

        $createUserResult = $this->userModel->create('cardtest_user', 'cardtest@example.com', 'password');
        $this->assertTrue($createUserResult, "setUp: Failed to create test user.");
        $user = $this->userModel->findByUsername('cardtest_user');
        if (!$user) {
            $this->fail("setUp: Failed to find test user 'cardtest_user' after creation.");
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

    private function createTestWordAndCard(int $boxNumber, ?string $nextReviewAtISO = null, ?string $lastReviewedAtISO = null): array
    {
        $wordGerman = 'TestWort_' . uniqid();
        $wordId = $this->wordModel->create($this->testUserId, $wordGerman, 'TestWordTranslation');
        $this->assertNotFalse($wordId, "Helper: Failed to create test word.");

        $createdAtISO = (new DateTime())->format('Y-m-d H:i:s');
        if ($nextReviewAtISO === null) {
            // Use model's calculation for default next review date if not specified
            // This requires LeitnerCard::calculateNextReviewDate to be public or use a different strategy
            // For simplicity, if null, and box 0, set to now. For other boxes, could set to now + interval.
            // The current model's public create() method correctly sets box 0 to now.
            // For this helper, we are manually inserting, so we must provide a date.
            // Let's make it "now" if not provided, to ensure it's due for some tests.
             $reviewAtISO = $createdAtISO;
        } else {
            $reviewAtISO = $nextReviewAtISO;
        }

        $sql = "INSERT INTO leitner_cards (word_id, user_id, box_number, created_at, next_review_at, last_reviewed_at)
                VALUES (:word_id, :user_id, :box_number, :created_at, :next_review_at, :last_reviewed_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':word_id', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->testUserId, PDO::PARAM_INT);
        $stmt->bindParam(':box_number', $boxNumber, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $createdAtISO);
        $stmt->bindParam(':next_review_at', $reviewAtISO);
        $stmt->bindParam(':last_reviewed_at', $lastReviewedAtISO);

        $this->assertTrue($stmt->execute(), "Helper: Manual card insertion failed for box {$boxNumber}.");
        $cardId = (int)$this->pdo->lastInsertId();
        $this->assertGreaterThan(0, $cardId, "Helper: Manual card insertion returned invalid ID.");

        $card = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($card, "Helper: Failed to retrieve manually inserted card by ID {$cardId}.");
        return $card;
    }

    public function testNewCardIsInBox0AndDueImmediately()
    {
        $wordId = $this->wordModel->create($this->testUserId, 'NewWort', 'NewWord');
        $this->assertNotFalse($wordId);
        $cardId = $this->leitnerCardModel->create($wordId, $this->testUserId);
        $this->assertNotFalse($cardId, "Card creation returned false.");

        $card = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($card);
        $this->assertEquals(0, $card['box_number'], "New card should be in Box 0.");

        $nextReviewTime = (new DateTime($card['next_review_at']))->getTimestamp();
        $currentTime = (new DateTime())->getTimestamp();
        $this->assertLessThanOrEqual($currentTime + 60, $nextReviewTime, "New card in Box 0 should be due almost immediately.");
        $this->assertGreaterThanOrEqual($currentTime - 60, $nextReviewTime, "New card in Box 0 review time should not be in the past significantly.");
    }

    public function testReviewBox0Correct() {
        $card = $this->createTestWordAndCard(0);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_CORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(3, $updatedCard['box_number'], "Box 0 Correct -> Box 3");
    }
    public function testReviewBox0Partial() {
        $card = $this->createTestWordAndCard(0);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_PARTIAL);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(2, $updatedCard['box_number'], "Box 0 Partial -> Box 2");
    }
    public function testReviewBox0Incorrect() {
        $card = $this->createTestWordAndCard(0);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_INCORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(1, $updatedCard['box_number'], "Box 0 Incorrect -> Box 1");
    }
    public function testReviewBox1Correct() {
        $card = $this->createTestWordAndCard(1);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_CORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(2, $updatedCard['box_number'], "Box 1 Correct -> Box 2");
    }
    public function testReviewBox1Partial() {
        $card = $this->createTestWordAndCard(1);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_PARTIAL);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(1, $updatedCard['box_number']);
        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+'.LeitnerCard::BOX_INTERVALS[1].' days');
        $this->assertEquals($expectedNextReview->format('Y-m-d'), (new DateTime($updatedCard['next_review_at']))->format('Y-m-d'));
    }
     public function testReviewBox1Incorrect() {
        $card = $this->createTestWordAndCard(1);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_INCORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(1, $updatedCard['box_number']);
        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+'.LeitnerCard::BOX_INTERVALS[1].' days');
        $this->assertEquals($expectedNextReview->format('Y-m-d'), (new DateTime($updatedCard['next_review_at']))->format('Y-m-d'));
    }
    public function testReviewIntermediateBoxCorrect() {
        $card = $this->createTestWordAndCard(5);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_CORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(6, $updatedCard['box_number']);
    }
    public function testReviewIntermediateBoxPartial() {
        $card = $this->createTestWordAndCard(5);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_PARTIAL);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(5, $updatedCard['box_number']);
        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+'.LeitnerCard::BOX_INTERVALS[5].' days');
        $this->assertEquals($expectedNextReview->format('Y-m-d'), (new DateTime($updatedCard['next_review_at']))->format('Y-m-d'));
    }
    public function testReviewIntermediateBoxIncorrect() {
        $card = $this->createTestWordAndCard(5);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_INCORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(4, $updatedCard['box_number']);
    }
    public function testReviewBoxMaxCorrectToMastery() {
        $card = $this->createTestWordAndCard(LeitnerCard::MAX_BOX_NUMBER);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_CORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(LeitnerCard::MAX_BOX_NUMBER + 1, $updatedCard['box_number']);
    }
     public function testReviewBoxMaxPartial() {
        $card = $this->createTestWordAndCard(LeitnerCard::MAX_BOX_NUMBER);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_PARTIAL);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(LeitnerCard::MAX_BOX_NUMBER, $updatedCard['box_number']);
        $expectedNextReview = (new DateTime($updatedCard['last_reviewed_at']))->modify('+'.LeitnerCard::BOX_INTERVALS[LeitnerCard::MAX_BOX_NUMBER].' days');
        $this->assertEquals($expectedNextReview->format('Y-m-d'), (new DateTime($updatedCard['next_review_at']))->format('Y-m-d'));
    }
    public function testReviewBoxMaxIncorrect() {
        $card = $this->createTestWordAndCard(LeitnerCard::MAX_BOX_NUMBER);
        $result = $this->leitnerCardModel->processReview($card['id'], $this->testUserId, LeitnerCard::OUTCOME_INCORRECT);
        $this->assertTrue($result);
        $updatedCard = $this->leitnerCardModel->getCardById($card['id'], $this->testUserId);
        $this->assertEquals(LeitnerCard::MAX_BOX_NUMBER - 1, $updatedCard['box_number']);
    }
    public function testWordDeletionCascadesToLeitnerCard() {
        $wordId = $this->wordModel->create($this->testUserId, 'CascadeTest', 'Test');
        $this->assertNotFalse($wordId);
        $cardId = $this->leitnerCardModel->create($wordId, $this->testUserId);
        $this->assertNotFalse($cardId);
        $card = $this->leitnerCardModel->getCardById($cardId, $this->testUserId);
        $this->assertNotNull($card);
        $this->wordModel->delete($wordId, $this->testUserId);
        $this->assertNull($this->leitnerCardModel->getCardById($cardId, $this->testUserId));
    }

    public function testGetCardStats() {
        $this->createTestWordAndCard(0, (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(1, (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(1, (new DateTime())->modify('+1 day')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(7, (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(11, (new DateTime())->modify('+11 days')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(LeitnerCard::MAX_BOX_NUMBER + 1, (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'));

        $stats = $this->leitnerCardModel->getCardStats($this->testUserId);

        $this->assertEquals(1, $stats[0], "Box 0 count.");
        $this->assertEquals(2, $stats[1], "Box 1 count.");
        $this->assertEquals(0, $stats[2], "Box 2 count.");
        $this->assertEquals(1, $stats[7], "Box 7 count.");
        $this->assertEquals(1, $stats[11], "Box 11 count.");
        $this->assertEquals(1, $stats[LeitnerCard::MAX_BOX_NUMBER + 1], "Mastered count.");
        $this->assertEquals(2, $stats['due'], "Due cards count (Box 0 + one Box 1 made due).");
    }

    public function testGetSystemWideBoxDistribution() {
        // User 1
        $this->createTestWordAndCard(0);
        $this->createTestWordAndCard(1);
        $this->createTestWordAndCard(11);
        $this->createTestWordAndCard(LeitnerCard::MAX_BOX_NUMBER + 1); // Mastered (12)

        // User 2
        $createUserResult = $this->userModel->create('user2_dist_sys', 'u2distsys@example.com', 'p');
        $this->assertTrue($createUserResult);
        $user2 = $this->userModel->findByUsername('user2_dist_sys');
        $user2Id = $user2['id'];

        $wordU2B0Id = $this->wordModel->create($user2Id, 'U2B0Sys', 'U2B0TSys');
        $this->leitnerCardModel->create($wordU2B0Id, $user2Id); // Box 0 for User 2

        $wordU2B10Id = $this->wordModel->create($user2Id, 'U2B10Sys', 'U2B10TSys');
        $this->assertNotFalse($wordU2B10Id);
        // Manually insert card for User 2 in Box 10
        $this->pdo->exec("INSERT INTO leitner_cards (word_id, user_id, box_number, created_at, next_review_at) VALUES ({$wordU2B10Id}, {$user2Id}, 10, datetime('now'), datetime('now'))");

        $distribution = $this->leitnerCardModel->getSystemWideBoxDistribution();

        $this->assertEquals(2, $distribution['Box 0 (Acquaintance)'] ?? 0);
        $this->assertEquals(1, $distribution['Box 1'] ?? 0);
        $this->assertEquals(0, $distribution['Box 5'] ?? 0);
        $this->assertEquals(1, $distribution['Box 10'] ?? 0);
        $this->assertEquals(1, $distribution['Box 11'] ?? 0);
        $this->assertEquals(1, $distribution['Mastered (Box >'.LeitnerCard::MAX_BOX_NUMBER.')'] ?? 0);
    }

    public function testGetDueCards() {
        $card1_data = $this->createTestWordAndCard(0, (new DateTime())->modify('-1 minute')->format('Y-m-d H:i:s'));
        $card2_data = $this->createTestWordAndCard(1, (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(5, (new DateTime())->modify('+5 days')->format('Y-m-d H:i:s'));
        $this->createTestWordAndCard(LeitnerCard::MAX_BOX_NUMBER + 1, (new DateTime())->modify('-10 days')->format('Y-m-d H:i:s'));

        $dueCards = $this->leitnerCardModel->getDueCards($this->testUserId, 10);
        $this->assertCount(2, $dueCards, "Should fetch 2 due cards (Box 0 and Box 1 card).");

        $dueCardIds = array_map(function($card){ return $card['id']; }, $dueCards);
        $this->assertContains($card1_data['id'], $dueCardIds);
        $this->assertContains($card2_data['id'], $dueCardIds);
    }

    public function testCountCardsByUserId() {
        $wordId1 = $this->wordModel->create($this->testUserId, 'W1Cnt', 'T1Cnt'); $this->leitnerCardModel->create($wordId1, $this->testUserId);
        $wordId2 = $this->wordModel->create($this->testUserId, 'W2Cnt', 'T2Cnt'); $this->leitnerCardModel->create($wordId2, $this->testUserId);
        $this->assertEquals(2, $this->leitnerCardModel->countCardsByUserId($this->testUserId));
    }

    public function testCountDueCardsByUserId() {
        $wordId1 = $this->wordModel->create($this->testUserId, 'W1DueCnt', 'T1DueCnt');
        $this->leitnerCardModel->create($wordId1, $this->testUserId); //Box 0, due

        $wordId2 = $this->wordModel->create($this->testUserId, 'W2NDueCnt', 'T2NDueCnt');
        $cardId2 = $this->leitnerCardModel->create($wordId2, $this->testUserId); //Box 0, due

        $futureDate = (new DateTime())->modify('+5 days')->format('Y-m-d H:i:s');
        // For this card, make it not due by moving to Box 1 and setting future review date
        $this->pdo->exec("UPDATE leitner_cards SET box_number = 1, next_review_at = '{$futureDate}' WHERE id = {$cardId2}");

        $this->assertEquals(1, $this->leitnerCardModel->countDueCardsByUserId($this->testUserId));
    }

    public function testCountAllLeitnerCards() {
        $wordId1 = $this->wordModel->create($this->testUserId, 'W1AllCnt', 'T1AllCnt'); $this->leitnerCardModel->create($wordId1, $this->testUserId);
        $createUserResult = $this->userModel->create('user2lc_all', 'u2lc_all@example.com', 'p'); $this->assertTrue($createUserResult);
        $user2 = $this->userModel->findByUsername('user2lc_all'); $user2Id = $user2['id'];
        $wordIdUser2 = $this->wordModel->create($user2Id, 'W1U2All', 'T1U2All'); $this->leitnerCardModel->create($wordIdUser2, $user2Id);
        $this->assertEquals(2, $this->leitnerCardModel->countAll());
    }

    public function testCountAllDueTodaySystemWide() {
        $wordId1 = $this->wordModel->create($this->testUserId, 'W1SysDue', 'T1SysDue');
        $this->leitnerCardModel->create($wordId1, $this->testUserId); // Due

        $createUserResult = $this->userModel->create('user2swd_all', 'u2swd_all@example.com', 'p'); $this->assertTrue($createUserResult);
        $user2 = $this->userModel->findByUsername('user2swd_all'); $user2Id = $user2['id'];
        $wordIdU2 = $this->wordModel->create($user2Id, 'W1U2SysDue', 'T1U2SysDue');
        $this->leitnerCardModel->create($wordIdU2, $user2Id); // Due

        $wordIdU2N = $this->wordModel->create($user2Id, 'W2U2SysND', 'T2U2SysND');
        $cardIdU2N = $this->leitnerCardModel->create($wordIdU2N, $user2Id); // Due initially
        $futureDate = (new DateTime())->modify('+3 days')->format('Y-m-d H:i:s');
        $this->pdo->exec("UPDATE leitner_cards SET box_number = 1, next_review_at = '{$futureDate}' WHERE id = {$cardIdU2N}"); // Not due

        $this->assertEquals(2, $this->leitnerCardModel->countAllDueToday());
    }

    protected function tearDown(): void {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
        Database::resetInstance();
        $this->pdo = null;
    }
}
