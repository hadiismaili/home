<?php
namespace App\Models;

use PDO;
use App\Core\Database;

class LearningSet {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function create(string $name, ?string $description, int $adminId): int|false {
        $sql = "INSERT INTO learning_sets (name, description, admin_id) VALUES (:name, :description, :admin_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $lastId = $this->db->lastInsertId();
            return $lastId ? (int)$lastId : false;
        }
        return false;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT ls.*, u.username as admin_username
                                    FROM learning_sets ls
                                    LEFT JOIN users u ON ls.admin_id = u.id
                                    WHERE ls.id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function findByName(string $name): ?array {
        $stmt = $this->db->prepare("SELECT * FROM learning_sets WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, string $name, ?string $description): bool {
        $sql = "UPDATE learning_sets SET name = :name, description = :description WHERE id = :id";
        // updated_at is handled by trigger
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function delete(int $id): bool {
        // ON DELETE CASCADE on learning_set_words and user_leitner_progress will handle related data.
        $stmt = $this->db->prepare("DELETE FROM learning_sets WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function getAll(string $orderBy = 'name', string $orderDir = 'ASC'): array {
        $allowedOrderBy = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array(strtolower($orderBy), $allowedOrderBy, true)) $orderBy = 'name';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT ls.*, u.username as admin_username,
                       (SELECT COUNT(*) FROM learning_set_words lsw WHERE lsw.learning_set_id = ls.id) as word_count
                FROM learning_sets ls
                LEFT JOIN users u ON ls.admin_id = u.id
                ORDER BY " . $orderBy . " " . $orderDir; // Safe due to whitelisting
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM learning_sets");
        return (int)$stmt->fetchColumn();
    }

    public function addWordToSet(int $setId, int $globalWordId): bool {
        $stmt_check = $this->db->prepare("SELECT id FROM learning_set_words WHERE learning_set_id = :set_id AND global_word_id = :word_id");
        $stmt_check->bindParam(':set_id', $setId, PDO::PARAM_INT);
        $stmt_check->bindParam(':word_id', $globalWordId, PDO::PARAM_INT);
        $stmt_check->execute();
        if ($stmt_check->fetch()) {
            return true;
        }

        $sql = "INSERT INTO learning_set_words (learning_set_id, global_word_id) VALUES (:set_id, :word_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':set_id', $setId, PDO::PARAM_INT);
        $stmt->bindParam(':word_id', $globalWordId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function removeWordFromSet(int $setId, int $globalWordId): bool {
        $sql = "DELETE FROM learning_set_words WHERE learning_set_id = :set_id AND global_word_id = :word_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':set_id', $setId, PDO::PARAM_INT);
        $stmt->bindParam(':word_id', $globalWordId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function getWordsInSet(int $setId, bool $idOnly = false): array {
        // Corrected SQL to ensure no ambiguity and safe column list
        $fieldsToSelect = $idOnly ? "gwb.id" : "gwb.id, gwb.german_word, gwb.translation, gwb.persian_phonetic_pronunciation, gwb.word_type, gwb.word_gender, gwb.word_level, gwb.example_german, gwb.example_persian_translation, gwb.audio_url, gwb.created_at, gwb.updated_at";
        $sql = "SELECT " . $fieldsToSelect .
               " FROM global_word_bank gwb
                JOIN learning_set_words lsw ON gwb.id = lsw.global_word_id
                WHERE lsw.learning_set_id = :set_id
                ORDER BY gwb.german_word ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':set_id', $setId, PDO::PARAM_INT);
        $stmt->execute();
        return $idOnly ? $stmt->fetchAll(PDO::FETCH_COLUMN) : $stmt->fetchAll();
    }

    public function countWordsInSet(int $setId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM learning_set_words WHERE learning_set_id = :set_id");
        $stmt->bindParam(':set_id', $setId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn(); // Corrected: removed backslash
    }
    public function getDbConnection(): PDO { return $this->db; }
}
