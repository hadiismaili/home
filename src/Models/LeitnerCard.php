<?php

namespace App\Models;

use PDO;
use App\Core\Database;
use DateTime;

class LeitnerCard {
    private PDO $db;

    // Box 0 is 'Acquaintance Box'
    // Mastery is box_number = MAX_BOX_NUMBER + 1 (i.e., 12)
    public const MAX_BOX_NUMBER = 11;
    public const BOX_INTERVALS = [ // For boxes 1 through 11
        1 => 1,
        2 => 3,
        3 => 7,
        4 => 14,
        5 => 30,
        6 => 60,
        7 => 90,  // 3 months
        8 => 120, // 4 months
        9 => 180, // 6 months
        10 => 240, // 8 months
        11 => 365  // 1 year
    ];
    // Define review outcomes
    public const OUTCOME_CORRECT = 'correct';
    public const OUTCOME_INCORRECT = 'incorrect';
    public const OUTCOME_PARTIAL = 'partial';


    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function create(int $wordId, int $userId): int|false {
        $now = new DateTime();
        $currentBoxNumber = 0;
        $nextReviewAt = clone $now;

        $sql = "INSERT INTO leitner_cards (word_id, user_id, box_number, created_at, next_review_at, last_reviewed_at)
                VALUES (:word_id, :user_id, :box_number, :created_at, :next_review_at, NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':word_id', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':box_number', $currentBoxNumber, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $now->format('Y-m-d H:i:s'));
        $stmt->bindParam(':next_review_at', $nextReviewAt->format('Y-m-d H:i:s'));

        if ($stmt->execute()) {
            $lastId = $this->db->lastInsertId();
            return $lastId ? (int)$lastId : false;
        }
        return false;
    }

    public function findByWordId(int $wordId, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT lc.*, w.german_word, w.translation, w.audio_filename
                                    FROM leitner_cards lc
                                    JOIN words w ON lc.word_id = w.id
                                    WHERE lc.word_id = :word_id AND lc.user_id = :user_id");
        $stmt->bindParam(':word_id', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $card = $stmt->fetch();
        return $card ?: null;
    }

    public function getCardById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM leitner_cards WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $card = $stmt->fetch();
        return $card ?: null;
    }

    public function getDueCards(int $userId, int $limit = 10): array {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sql = "SELECT lc.*, w.german_word, w.translation, w.audio_filename
                FROM leitner_cards lc
                JOIN words w ON lc.word_id = w.id
                WHERE lc.user_id = :user_id AND lc.next_review_at <= :now AND lc.box_number <= :max_box_number
                ORDER BY lc.box_number ASC, lc.next_review_at ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':now', $now);
        $maxBoxParam = self::MAX_BOX_NUMBER;
        $stmt->bindParam(':max_box_number', $maxBoxParam, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCardStats(int $userId): array {
        $sql = "SELECT box_number, COUNT(*) as count FROM leitner_cards WHERE user_id = :user_id GROUP BY box_number";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $statsByBox = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $allStats = [];
        $allStats[0] = $statsByBox[0] ?? 0;
        for($i = 1; $i <= self::MAX_BOX_NUMBER; $i++) {
            $allStats[$i] = $statsByBox[$i] ?? 0;
        }
        $masteredCount = 0;
        foreach ($statsByBox as $boxNum => $count) {
            if ((int)$boxNum > self::MAX_BOX_NUMBER) {
                $masteredCount += $count;
            }
        }
        $allStats[self::MAX_BOX_NUMBER + 1] = $masteredCount;

        $allStats['due'] = $this->countDueCardsByUserId($userId);
        return $allStats;
    }

    public function processReview(int $cardId, int $userId, string $outcome): bool {
        $card = $this->getCardById($cardId, $userId);
        if (!$card) return false; // Corrected variable name

        $currentBox = (int)$card['box_number']; // Corrected variable name
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
        $nextReviewAt = $this->calculateNextReviewDate($now, $newBox);

        $sql = "UPDATE leitner_cards
                SET box_number = :box_number, last_reviewed_at = :last_reviewed_at, next_review_at = :next_review_at
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':box_number', $newBox, PDO::PARAM_INT);
        $stmt->bindParam(':last_reviewed_at', $now->format('Y-m-d H:i:s'));
        $stmt->bindParam(':next_review_at', $nextReviewAt->format('Y-m-d H:i:s'));
        $stmt->bindParam(':id', $cardId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()){
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    private function calculateNextReviewDate(DateTime $baseDate, int $boxNumber): DateTime {
        if ($boxNumber === 0) {
            return (clone $baseDate);
        }
        if ($boxNumber > self::MAX_BOX_NUMBER) {
            return (clone $baseDate)->modify('+100 years');
        }
        if (!isset(self::BOX_INTERVALS[$boxNumber])) {
            // Fallback: if boxNumber is valid (e.g. 1-11) but somehow not in BOX_INTERVALS array
            // or if it's an invalid positive number, default to Box 1 interval.
            $daysToAdd = self::BOX_INTERVALS[1];
            return (clone $baseDate)->modify("+$daysToAdd days");
        }
        $daysToAdd = self::BOX_INTERVALS[$boxNumber];
        return (clone $baseDate)->modify("+$daysToAdd days");
    }

    public function countCardsByUserId(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM leitner_cards WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countDueCardsByUserId(int $userId): int {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sqlDue = "SELECT COUNT(*) FROM leitner_cards
                     WHERE user_id = :user_id AND next_review_at <= :now AND box_number <= :max_box_number";
        $stmtDue = $this->db->prepare($sqlDue);
        $stmtDue->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtDue->bindParam(':now', $now);
        $maxBoxParam = self::MAX_BOX_NUMBER;
        $stmtDue->bindParam(':max_box_number', $maxBoxParam, PDO::PARAM_INT);
        $stmtDue->execute();
        return (int)$stmtDue->fetchColumn();
    }

    public function getSystemWideBoxDistribution(): array {
        $sql = "SELECT box_number, COUNT(*) as count FROM leitner_cards GROUP BY box_number ORDER BY box_number ASC";
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

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM leitner_cards");
        return (int)$stmt->fetchColumn();
    }

    public function countAllDueToday(): int {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM leitner_cards WHERE next_review_at <= :now AND box_number <= :max_box_number");
        $stmt->bindParam(':now', $now);
        $maxBoxParam = self::MAX_BOX_NUMBER;
        $stmt->bindParam(':max_box_number', $maxBoxParam, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
