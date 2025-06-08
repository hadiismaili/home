<?php
namespace App\Models;

use PDO;
use App\Core\Database;

class GlobalWord {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO global_word_bank (
                    german_word, translation, persian_phonetic_pronunciation,
                    word_type, word_gender, word_level,
                    example_german, example_persian_translation, audio_url
                ) VALUES (
                    :german_word, :translation, :persian_phonetic,
                    :word_type, :word_gender, :word_level,
                    :example_german, :example_persian, :audio_url
                )";
        $stmt = $this->db->prepare($sql);
        // Ensure all keys exist in $data or provide defaults
        $stmt->bindParam(':german_word', $data['german_word']);
        $stmt->bindParam(':translation', $data['translation']);
        $stmt->bindParam(':persian_phonetic', $data['persian_phonetic_pronunciation']);
        $stmt->bindParam(':word_type', $data['word_type']);
        $stmt->bindParam(':word_gender', $data['word_gender']);
        $stmt->bindParam(':word_level', $data['word_level']);
        $stmt->bindParam(':example_german', $data['example_german']);
        $stmt->bindParam(':example_persian', $data['example_persian_translation']);
        $stmt->bindParam(':audio_url', $data['audio_url']);

        if ($stmt->execute()) {
            $lastId = $this->db->lastInsertId();
            return $lastId ? (int)$lastId : false;
        }
        return false;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM global_word_bank WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function findByGermanWord(string $germanWord): ?array {
        $stmt = $this->db->prepare("SELECT * FROM global_word_bank WHERE german_word = :german_word");
        $stmt->bindParam(':german_word', $germanWord);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE global_word_bank SET
                    german_word = :german_word, translation = :translation,
                    persian_phonetic_pronunciation = :persian_phonetic,
                    word_type = :word_type, word_gender = :word_gender, word_level = :word_level,
                    example_german = :example_german, example_persian_translation = :example_persian,
                    audio_url = :audio_url
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':german_word', $data['german_word']);
        $stmt->bindParam(':translation', $data['translation']);
        $stmt->bindParam(':persian_phonetic', $data['persian_phonetic_pronunciation']);
        $stmt->bindParam(':word_type', $data['word_type']);
        $stmt->bindParam(':word_gender', $data['word_gender']);
        $stmt->bindParam(':word_level', $data['word_level']);
        $stmt->bindParam(':example_german', $data['example_german']);
        $stmt->bindParam(':example_persian', $data['example_persian_translation']);
        $stmt->bindParam(':audio_url', $data['audio_url']);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM global_word_bank WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function getAll(int $limit = 100, int $offset = 0, string $orderBy = 'german_word', string $orderDir = 'ASC'): array {
        $allowedOrderBy = ['id', 'german_word', 'word_level', 'word_type', 'created_at', 'updated_at'];
        if (!in_array(strtolower($orderBy), $allowedOrderBy, true)) $orderBy = 'german_word'; // Strict check
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM global_word_bank ORDER BY " . $orderBy . " " . $orderDir . " LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM global_word_bank");
        return (int)$stmt->fetchColumn();
    }
     public function getDbConnection(): PDO { return $this->db; }
}
