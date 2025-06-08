<?php
namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\UserProgressService;
use App\Models\User;
use App\Models\GlobalWord;
use App\Models\LearningSet;
use App\Core\Database;
use PDO;
use DateTime;

class UserProgressServiceTest extends TestCase
{
    private ?PDO $pdo = null;
    private User $userModel;
    private GlobalWord $globalWordModel;
    private LearningSet $learningSetModel;
    private UserProgressService $progressService;

    private int $testUserId;
    private int $testSetId;
    private int $globalWordId1;
    private int $globalWordId2;

    protected function setUp(): void
    {
        Database::resetInstance();
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->assertNotNull($this->pdo, "PDO connection must be established in setUp.");

        $this->pdo->exec("DELETE FROM user_leitner_progress");
        $this->pdo->exec("DELETE FROM learning_set_words");
        $this->pdo->exec("DELETE FROM learning_sets");
        $this->pdo->exec("DELETE FROM global_word_bank");
        $this->pdo->exec("DELETE FROM users");

        $this->userModel = new User();
        $this->globalWordModel = new GlobalWord();
        $this->learningSetModel = new LearningSet();
        $this->progressService = new UserProgressService();

        $createUserResult = $this->userModel->create('progress_user', 'progress@example.com', 'password');
        $this->assertTrue($createUserResult);
        $user = $this->userModel->findByUsername('progress_user');
        $this->assertNotNull($user);
        $this->testUserId = $user['id'];

        $adminUserResult = $this->userModel->create('progress_admin', 'progress_admin@example.com', 'password', true);
        $this->assertTrue($adminUserResult);
        $adminUser = $this->userModel->findByUsername('progress_admin');
        $this->assertNotNull($adminUser);

        $this->globalWordId1 = $this->globalWordModel->create(['german_word' => 'WortEins', 'translation' => 'One']);
        $this->assertNotFalse($this->globalWordId1);
        $this->globalWordId2 = $this->globalWordModel->create(['german_word' => 'WortZwei', 'translation' => 'Two']);
        $this->assertNotFalse($this->globalWordId2);

        $this->testSetId = $this->learningSetModel->create('Test Progress Set', 'For testing progress', $adminUser['id']);
        $this->assertNotFalse($this->testSetId);
        $this->learningSetModel->addWordToSet($this->testSetId, $this->globalWordId1);
        $this->learningSetModel->addWordToSet($this->testSetId, $this->globalWordId2);

        if (session_status() == PHP_SESSION_ACTIVE) { session_unset(); session_destroy(); }
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) { @session_start(); }
        $_SESSION = [];
    }

    private function assertBoxAndNextReviewDate($progressId, $expectedBox, $expectedIntervalDays = null, $baseDate = null) {
        $progress = $this->progressService->getProgressById($progressId, $this->testUserId);
        $this->assertNotNull($progress, "Progress record not found for ID $progressId");
        $this->assertEquals($expectedBox, $progress['box_number'], "Box number mismatch for progress ID $progressId.");

        $baseForCalc = $baseDate ? (new DateTime($baseDate)) : (new DateTime($progress['last_reviewed_at'] ?? 'now'));

        if ($expectedIntervalDays !== null) {
            $expectedDate = $baseForCalc->modify("+$expectedIntervalDays days")->format('Y-m-d');
            $actualDate = (new DateTime($progress['next_review_at']))->format('Y-m-d');
            $this->assertEquals($expectedDate, $actualDate, "Next review date mismatch for box $expectedBox.");
        } elseif ($expectedBox === 0) {
             $nextReviewTime = (new DateTime($progress['next_review_at']))->getTimestamp();
             $this->assertLessThanOrEqual((new DateTime())->getTimestamp() + 60, $nextReviewTime, "Box 0 due date not immediate enough.");
        } elseif ($expectedBox > UserProgressService::MAX_BOX_NUMBER) {
            $expectedDate = $baseForCalc->modify('+100 years')->format('Y-m-d');
            $actualDate = (new DateTime($progress['next_review_at']))->format('Y-m-d');
            $this->assertEquals($expectedDate, $actualDate, "Mastered card review date mismatch.");
        }
    }

    public function testCreateOrResetProgressNewWord() {
        $progressId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->assertNotFalse($progressId);
        $this->assertIsInt($progressId);
        $this->assertBoxAndNextReviewDate($progressId, 0);
    }

    public function testCreateOrResetProgressExistingWordResetsToBox0() {
        $progressId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->pdo->exec("UPDATE user_leitner_progress SET box_number = 3, next_review_at = datetime('now', '+7 days') WHERE id = $progressId");
        $updatedProgress = $this->progressService->getProgressById($progressId, $this->testUserId);
        $this->assertEquals(3, $updatedProgress['box_number']); // Confirm it's in Box 3

        $resetProgressId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->assertEquals($progressId, $resetProgressId);
        $this->assertBoxAndNextReviewDate($resetProgressId, 0);
    }

    public function testProcessReviewBox0Correct() {
        $pId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->progressService->processReview($pId, $this->testUserId, UserProgressService::OUTCOME_CORRECT);
        $this->assertBoxAndNextReviewDate($pId, 3, UserProgressService::BOX_INTERVALS[3]);
    }
    public function testProcessReviewBox0Partial() {
        $pId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->progressService->processReview($pId, $this->testUserId, UserProgressService::OUTCOME_PARTIAL);
        $this->assertBoxAndNextReviewDate($pId, 2, UserProgressService::BOX_INTERVALS[2]);
    }
    public function testProcessReviewBox0Incorrect() {
        $pId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->progressService->processReview($pId, $this->testUserId, UserProgressService::OUTCOME_INCORRECT);
        $this->assertBoxAndNextReviewDate($pId, 1, UserProgressService::BOX_INTERVALS[1]);
    }

    public function testProcessReviewBox1Correct() {
        $pId = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId);
        $this->pdo->exec("UPDATE user_leitner_progress SET box_number = 1, last_reviewed_at = datetime('now', '-1 day'), next_review_at = datetime('now', '-1 day') WHERE id = $pId");
        $this->progressService->processReview($pId, $this->testUserId, UserProgressService::OUTCOME_CORRECT);
        $this->assertBoxAndNextReviewDate($pId, 2, UserProgressService::BOX_INTERVALS[2]);
    }

    public function testGetDueCardsForSet() {
        $pId1 = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId); // Due
        $pId2 = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId2, $this->testSetId); // Due
        // Make pId2 not due by moving it and setting future review date
        $futureDate = (new DateTime())->modify('+5 days')->format('Y-m-d H:i:s');
        $this->pdo->exec("UPDATE user_leitner_progress SET box_number=1, next_review_at='{$futureDate}' WHERE id = {$pId2}");

        $dueCards = $this->progressService->getDueCardsForSet($this->testUserId, $this->testSetId, 5);
        $this->assertCount(1, $dueCards);
        $this->assertEquals($this->globalWordId1, $dueCards[0]['global_word_id']);
    }

    public function testGetCardStatsForSet() {
        $pId1 = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId1, $this->testSetId); // Box 0
        $pId2 = $this->progressService->createOrResetProgress($this->testUserId, $this->globalWordId2, $this->testSetId); // Box 0
        // Move pId2 to Box 3, not due
        $futureDate = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
        $this->pdo->exec("UPDATE user_leitner_progress SET box_number=3, next_review_at='{$futureDate}' WHERE id = {$pId2}");

        $stats = $this->progressService->getCardStatsForSet($this->testUserId, $this->testSetId);
        $this->assertEquals(1, $stats[0], "Box 0 count");
        $this->assertEquals(0, $stats[1], "Box 1 count");
        $this->assertEquals(1, $stats[3], "Box 3 count");
        $this->assertEquals(1, $stats['due'], "Due count (only pId1 from Box 0)");
    }

    protected function tearDown(): void {
        if (session_status() == PHP_SESSION_ACTIVE) { session_unset(); session_destroy(); }
        $_SESSION = [];
        Database::resetInstance();
        $this->pdo = null;
    }
}
