<?php

namespace App\Models;

use PDO;
use App\Core\Database;

class Word {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getDbConnection(): PDO {
        return $this->db;
    }

    public function create(int $userId, string $germanWord, string $translation, ?string $audioFilename = null): int|false {
        $sql = "INSERT INTO words (user_id, german_word, translation, audio_filename) VALUES (:user_id, :german_word, :translation, :audio_filename)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':german_word', $germanWord);
        $stmt->bindParam(':translation', $translation);
        $stmt->bindParam(':audio_filename', $audioFilename);

        if ($stmt->execute()) {
            $lastId = $this->db->lastInsertId();
            return $lastId ? (int)$lastId : false;
        }
        return false;
    }

    public function findById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM words WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $word = $stmt->fetch();
        return $word ?: null;
    }

    public function findByGermanWord(string $germanWord, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM words WHERE german_word = :german_word AND user_id = :user_id");
        $stmt->bindParam(':german_word', $germanWord);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $word = $stmt->fetch();
        return $word ?: null;
    }

    public function getAllByUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM words WHERE user_id = :user_id ORDER BY created_at DESC, id DESC");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function update(int $id, int $userId, string $germanWord, string $translation, ?string $audioFilename = null): bool {
        $sql = "UPDATE words SET german_word = :german_word, translation = :translation, audio_filename = :audio_filename
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':german_word', $germanWord);
        $stmt->bindParam(':translation', $translation);
        $stmt->bindParam(':audio_filename', $audioFilename);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM words WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM words");
        return (int)$stmt->fetchColumn();
    }

    public function countWordsByUserId(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM words WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
