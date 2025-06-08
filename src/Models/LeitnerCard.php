<?php

namespace App\Models;

use PDO;
use App\Core\Database;
use DateTime;

class LeitnerCard {
    private PDO $db;
    public const BOX_INTERVALS = [1 => 1, 2 => 2, 3 => 4, 4 => 8, 5 => 16];
    public const MAX_BOX_NUMBER = 5;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function create(int $wordId, int $userId, int $boxNumber = 1): int|false {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $nextReviewAt = $this->calculateNextReviewDate(new DateTime($now), $boxNumber)->format('Y-m-d H:i:s');

        $sql = "INSERT INTO leitner_cards (word_id, user_id, box_number, created_at, next_review_at, last_reviewed_at)
                VALUES (:word_id, :user_id, :box_number, :created_at, :next_review_at, NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':word_id', $wordId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':box_number', $boxNumber, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':next_review_at', $nextReviewAt);

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
                                    WHERE lc.word_id = :word_id AND lc.user_id = :user_id"); // lc.user_id is correct here
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
                WHERE lc.user_id = :user_id AND lc.next_review_at <= :now AND lc.box_number <= :max_box_num
                ORDER BY lc.next_review_at ASC, lc.box_number ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':now', $now);
        $stmt->bindValue(':max_box_num', self::MAX_BOX_NUMBER, PDO::PARAM_INT); // Changed to bindValue
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
        for($i = 1; $i <= self::MAX_BOX_NUMBER + 1; $i++) {
            $allStats[$i] = $statsByBox[$i] ?? 0;
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sqlDue = "SELECT COUNT(*) FROM leitner_cards WHERE user_id = :user_id AND next_review_at <= :now AND box_number <= :max_box_num";
        $stmtDue = $this->db->prepare($sqlDue);
        $stmtDue->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtDue->bindParam(':now', $now);
        $stmtDue->bindValue(':max_box_num', self::MAX_BOX_NUMBER, PDO::PARAM_INT); // Changed to bindValue
        $stmtDue->execute();
        $allStats['due'] = (int) $stmtDue->fetchColumn(); // Ensure int

        return $allStats;
    }

    public function processReview(int $cardId, int $userId, bool $wasCorrect): bool {
        $card = $this->getCardById($cardId, $userId);
        if (!$card) return false;

        $newBoxNumber = $card['box_number'];
        if ($wasCorrect) {
            $newBoxNumber = min((int)$card['box_number'] + 1, self::MAX_BOX_NUMBER + 1);
        } else {
            $newBoxNumber = 1;
        }

        $now = new DateTime();
        $nextReviewAtDateTime = $this->calculateNextReviewDate($now, $newBoxNumber);
        $nextReviewAtStr = $nextReviewAtDateTime->format('Y-m-d H:i:s');
        $lastReviewedAtStr = $now->format('Y-m-d H:i:s');

        $sql = "UPDATE leitner_cards
                SET box_number = :box_number, last_reviewed_at = :last_reviewed_at, next_review_at = :next_review_at
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':box_number', $newBoxNumber, PDO::PARAM_INT);
        $stmt->bindParam(':last_reviewed_at', $lastReviewedAtStr);
        $stmt->bindParam(':next_review_at', $nextReviewAtStr);
        $stmt->bindParam(':id', $cardId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function calculateNextReviewDate(?DateTime $baseDateForCalc, int $boxNumber): DateTime {
        $baseDate = $baseDateForCalc ?? new DateTime();

        if ($boxNumber > self::MAX_BOX_NUMBER) {
            return (clone $baseDate)->modify('+100 years');
        }
        if (!isset(self::BOX_INTERVALS[$boxNumber])) {
             return (clone $baseDate)->modify('+1 day');
        }
        $daysToAdd = self::BOX_INTERVALS[$boxNumber];
        return (clone $baseDate)->modify("+$daysToAdd days");
    }
}
