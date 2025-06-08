<?php
namespace App\Models;

use PDO;
use App\Core\Database;
use DateTime;

class UserProgressService {
    private PDO $db;

    public const MAX_BOX_NUMBER = 11;
    public const BOX_INTERVALS = [
        1 => 1, 2 => 3, 3 => 7, 4 => 14, 5 => 30, 6 => 60,
        7 => 90, 8 => 120, 9 => 180, 10 => 240, 11 => 365
    ];
    public const OUTCOME_CORRECT = 'correct';
    public const OUTCOME_INCORRECT = 'incorrect';
    public const OUTCOME_PARTIAL = 'partial';

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function createOrResetProgress(int $userId, int $globalWordId, int $learningSetId): int|false {
        $existing = $this->findByUserWordSet($userId, $globalWordId, $learningSetId);
        $now = new DateTime();
        $nextReviewAt = clone $now;

        if ($existing) {
            $sql = "UPDATE user_leitner_progress SET box_number = 0, last_reviewed_at = NULL,
                        next_review_at = :next_review_at, updated_at = :updated_at
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $existing['id'], PDO::PARAM_INT);
            $stmt->bindParam(':next_review_at', $nextReviewAt->format('Y-m-d H:i:s'));
            $stmt->bindParam(':updated_at', $now->format('Y-m-d H:i:s'));
            if ($stmt->execute()) return $existing['id'];
            return false;
        } else {
            $sql = "INSERT INTO user_leitner_progress
                        (user_id, global_word_id, learning_set_id, box_number, next_review_at, created_at, updated_at)
                    VALUES (:user_id, :global_word_id, :learning_set_id, 0, :next_review_at, :created_at, :updated_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':global_word_id', $globalWordId, PDO::PARAM_INT);
            $stmt->bindParam(':learning_set_id', $learningSetId, PDO::PARAM_INT);
            $stmt->bindParam(':next_review_at', $nextReviewAt->format('Y-m-d H:i:s'));
            $stmt->bindParam(':created_at', $now->format('Y-m-d H:i:s'));
            $stmt->bindParam(':updated_at', $now->format('Y-m-d H:i:s'));
            if ($stmt->execute()) {
                 $lastId = $this->db->lastInsertId();
                 return $lastId ? (int)$lastId : false;
            }
            return false;
        }
    }

    public function findByUserWordSet(int $userId, int $globalWordId, int $learningSetId): ?array {
        $sql = "SELECT * FROM user_leitner_progress
                WHERE user_id = :user_id AND global_word_id = :global_word_id AND learning_set_id = :learning_set_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':global_word_id', $globalWordId, PDO::PARAM_INT);
        $stmt->bindParam(':learning_set_id', $learningSetId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function getProgressById(int $progressId, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM user_leitner_progress WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $progressId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function processReview(int $progressId, int $userId, string $outcome): bool {
        $progress = $this->getProgressById($progressId, $userId);
        if (!$progress) return false;

        $currentBox = (int)$progress['box_number'];
        $newBox = $currentBox;

        if ($currentBox === 0) {
            switch ($outcome) {
                case self::OUTCOME_CORRECT: $newBox = 3; break;
                case self::OUTCOME_PARTIAL: $newBox = 2; break;
                case self::OUTCOME_INCORRECT: $newBox = 1; break;
                default: return false;
            }
        } elseif ($currentBox === 1) {
            switch ($outcome) {
                case self::OUTCOME_CORRECT: $newBox = 2; break;
                case self::OUTCOME_PARTIAL: $newBox = 1; break;
                case self::OUTCOME_INCORRECT: $newBox = 1; break;
                default: return false;
            }
        } elseif ($currentBox >= 2 && $currentBox < self::MAX_BOX_NUMBER) {
            switch ($outcome) {
                case self::OUTCOME_CORRECT: $newBox = $currentBox + 1; break;
                case self::OUTCOME_PARTIAL: $newBox = $currentBox; break;
                case self::OUTCOME_INCORRECT: $newBox = $currentBox - 1; break;
                default: return false;
            }
        } elseif ($currentBox === self::MAX_BOX_NUMBER) {
            switch ($outcome) {
                case self::OUTCOME_CORRECT: $newBox = self::MAX_BOX_NUMBER + 1; break;
                case self::OUTCOME_PARTIAL: $newBox = self::MAX_BOX_NUMBER; break;
                case self::OUTCOME_INCORRECT: $newBox = $currentBox - 1; break;
                default: return false;
            }
        } else {
            return true;
        }

        $now = new DateTime();
        $nextReviewAtDate = $this->calculateNextReviewDate($now, $newBox);

        $sql = "UPDATE user_leitner_progress
                SET box_number = :box_number, last_reviewed_at = :last_reviewed_at, next_review_at = :next_review_at
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':box_number', $newBox, PDO::PARAM_INT);
        $stmt->bindParam(':last_reviewed_at', $now->format('Y-m-d H:i:s'));
        $stmt->bindParam(':next_review_at', $nextReviewAtDate->format('Y-m-d H:i:s'));
        $stmt->bindParam(':id', $progressId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()){ return $stmt->rowCount() > 0; }
        return false;
    }

    private function calculateNextReviewDate(DateTime $baseDate, int $boxNumber): DateTime {
        if ($boxNumber === 0) { return (clone $baseDate); }
        if ($boxNumber > self::MAX_BOX_NUMBER) { return (clone $baseDate)->modify('+100 years'); }
        if (!isset(self::BOX_INTERVALS[$boxNumber])) {
            $daysToAdd = self::BOX_INTERVALS[1];
            return (clone $baseDate)->modify("+$daysToAdd days");
        }
        $daysToAdd = self::BOX_INTERVALS[$boxNumber];
        return (clone $baseDate)->modify("+$daysToAdd days");
    }

    public function getDueCardsForSet(int $userId, int $learningSetId, int $limit = 10): array {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sql = "SELECT ulp.id as progress_id, ulp.box_number, ulp.next_review_at, ulp.last_reviewed_at,
                       ulp.global_word_id,  -- Explicitly select ulp.global_word_id
                       gwb.id as gwb_id,    -- Alias gwb.id to avoid collision
                       gwb.german_word, gwb.translation, gwb.persian_phonetic_pronunciation,
                       gwb.word_type, gwb.word_gender, gwb.word_level, gwb.example_german,
                       gwb.example_persian_translation, gwb.audio_url
                FROM user_leitner_progress ulp
                JOIN global_word_bank gwb ON ulp.global_word_id = gwb.id
                WHERE ulp.user_id = :user_id AND ulp.learning_set_id = :learning_set_id
                  AND ulp.next_review_at <= :now AND ulp.box_number <= :max_box
                ORDER BY ulp.box_number ASC, ulp.next_review_at ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':learning_set_id', $learningSetId, PDO::PARAM_INT);
        $stmt->bindParam(':now', $now);
        $maxBox = self::MAX_BOX_NUMBER;
        $stmt->bindParam(':max_box', $maxBox, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCardStatsForSet(int $userId, int $learningSetId): array {
        $sql = "SELECT box_number, COUNT(*) as count FROM user_leitner_progress
                WHERE user_id = :user_id AND learning_set_id = :learning_set_id
                GROUP BY box_number";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':learning_set_id', $learningSetId, PDO::PARAM_INT);
        $stmt->execute();
        $statsByBox = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $allStats = [];
        $allStats[0] = $statsByBox[0] ?? 0;
        for($i = 1; $i <= self::MAX_BOX_NUMBER; $i++) {
            $allStats[$i] = $statsByBox[$i] ?? 0;
        }
        $masteredCount = 0;
        foreach ($statsByBox as $boxNum => $count) {
            if ((int)$boxNum > self::MAX_BOX_NUMBER) $masteredCount += $count;
        }
        $allStats[self::MAX_BOX_NUMBER + 1] = $masteredCount;

        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sqlDue = "SELECT COUNT(*) FROM user_leitner_progress
                   WHERE user_id = :user_id AND learning_set_id = :learning_set_id
                     AND next_review_at <= :now AND box_number <= :max_box";
        $stmtDue = $this->db->prepare($sqlDue);
        $stmtDue->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtDue->bindParam(':learning_set_id', $learningSetId, PDO::PARAM_INT);
        $stmtDue->bindParam(':now', $now);
        $maxBoxConst = self::MAX_BOX_NUMBER;
        $stmtDue->bindParam(':max_box', $maxBoxConst, PDO::PARAM_INT);
        $stmtDue->execute();
        $allStats['due'] = (int)$stmtDue->fetchColumn();

        return $allStats;
    }

    public function deleteProgressForSet(int $userId, int $learningSetId): bool {
        $sql = "DELETE FROM user_leitner_progress WHERE user_id = :user_id AND learning_set_id = :learning_set_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':learning_set_id', $learningSetId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function countAllProgressRecords(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM user_leitner_progress");
        return (int)$stmt->fetchColumn();
    }

    public function countAllDueTodaySystemWide(): int {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_leitner_progress WHERE next_review_at <= :now AND box_number <= :max_box_number");
        $stmt->bindParam(':now', $now);
        $maxBox = self::MAX_BOX_NUMBER;
        $stmt->bindParam(':max_box_number', $maxBox, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getSystemWideBoxDistribution(): array {
        $sql = "SELECT box_number, COUNT(*) as count FROM user_leitner_progress GROUP BY box_number ORDER BY box_number ASC";
        $stmt = $this->db->query($sql);
        $rawDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fullDistribution = [];
        $fullDistribution["Box 0 (Acquaintance)"] = 0;
        for ($i = 1; $i <= self::MAX_BOX_NUMBER; $i++) {
            $fullDistribution["Box " . $i] = 0;
        }
        $fullDistribution["Mastered (Box >" . self::MAX_BOX_NUMBER . ")"] = 0;
        $otherBoxes = [];

        foreach ($rawDistribution as $row) {
            $boxNum = (int)$row['box_number'];
            $count = (int)$row['count'];

            if ($boxNum === 0) {
                $fullDistribution["Box 0 (Acquaintance)"] = $count;
            } elseif ($boxNum >= 1 && $boxNum <= self::MAX_BOX_NUMBER) {
                $fullDistribution["Box " . $boxNum] = $count;
            } elseif ($boxNum > self::MAX_BOX_NUMBER) {
                $fullDistribution["Mastered (Box >" . self::MAX_BOX_NUMBER . ")"] += $count;
            } else {
                $otherBoxes["Box " . $boxNum . " (Other - Unexpected)"] = $count;
            }
        }
        return array_merge($fullDistribution, $otherBoxes);
    }

    public function getDbConnection(): PDO { return $this->db; }
}
