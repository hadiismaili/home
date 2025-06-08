<?php

namespace App\Models;

use PDO;
use App\Core\Database;

class Word {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function create(
        int $userId,
        string $germanWord,
        string $translation,
        ?string $persianPhonetic = null,
        ?string $wordTypeGender = null,
        ?string $wordLevel = null,
        ?string $exampleGerman = null,
        ?string $examplePersian = null,
        ?string $audioUrl = null // Changed from audioFilename
    ): int|false {
        $sql = "INSERT INTO words (
                    user_id, german_word, translation,
                    persian_phonetic_pronunciation, word_type_and_gender, word_level,
                    example_german, example_persian_translation, audio_url
                ) VALUES (
                    :user_id, :german_word, :translation,
                    :persian_phonetic, :word_type_gender, :word_level,
                    :example_german, :example_persian, :audio_url
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':german_word', $germanWord);
        $stmt->bindParam(':translation', $translation);
        $stmt->bindParam(':persian_phonetic', $persianPhonetic);
        $stmt->bindParam(':word_type_gender', $wordTypeGender);
        $stmt->bindParam(':word_level', $wordLevel);
        $stmt->bindParam(':example_german', $exampleGerman);
        $stmt->bindParam(':example_persian', $examplePersian);
        $stmt->bindParam(':audio_url', $audioUrl); // Changed from audio_filename

        if ($stmt->execute()) {
            $lastId = $this->db->lastInsertId(); // Ensure this is correctly fetched
            return $lastId ? (int)$lastId : false; // lastInsertId can return string '0' or false on failure for some drivers/cases
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

    public function update(
        int $id,
        int $userId,
        string $germanWord,
        string $translation,
        ?string $persianPhonetic = null,
        ?string $wordTypeGender = null,
        ?string $wordLevel = null,
        ?string $exampleGerman = null,
        ?string $examplePersian = null,
        ?string $audioUrl = null // Changed from audioFilename
    ): bool {
        $sql = "UPDATE words SET
                    german_word = :german_word,
                    translation = :translation,
                    persian_phonetic_pronunciation = :persian_phonetic,
                    word_type_and_gender = :word_type_gender,
                    word_level = :word_level,
                    example_german = :example_german,
                    example_persian_translation = :example_persian,
                    audio_url = :audio_url
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':german_word', $germanWord);
        $stmt->bindParam(':translation', $translation);
        $stmt->bindParam(':persian_phonetic', $persianPhonetic);
        $stmt->bindParam(':word_type_gender', $wordTypeGender);
        $stmt->bindParam(':word_level', $wordLevel);
        $stmt->bindParam(':example_german', $exampleGerman);
        $stmt->bindParam(':example_persian', $examplePersian);
        $stmt->bindParam(':audio_url', $audioUrl); // Changed from audio_filename

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM words WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function getDbConnection(): PDO {
        return $this->db;
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
