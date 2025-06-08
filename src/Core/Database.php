<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private string $dbPath;
    private ?PDO $pdo = null;

    public function __construct() {
        if (getenv('APP_ENV') === 'testing') {
            $dbName = getenv('DB_DATABASE_TEST') ?: ':memory:';
            $this->dbPath = ($dbName === ':memory:') ? ':memory:' : __DIR__ . '/../../' . $dbName;
        } else {
            $this->dbPath = __DIR__ . '/../../database/app.db';
        }
        // Crucially, do not call resetInstance() here in testing mode,
        // as each new Model would get a fresh DB, breaking tests.
        // resetInstance() should be called in test setUp().
    }

    public function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $this->pdo = new PDO('sqlite:' . $this->dbPath);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');

                self::$instance = $this->pdo;
                $this->initDatabase();
            } catch (PDOException $e) {
                // Consider logging this error instead of dying in a real application
                die("Database connection failed: " . $e->getMessage());
            }
        } else {
            $this->pdo = self::$instance;
        }
        return self::$instance;
    }

    private function initDatabase(): void {
        if ($this->pdo === null) {
            // This path should ideally not be hit if getConnection is always used prior.
            // Re-establish connection for robustness if pdo is null but static instance exists or needs creating.
            if (self::$instance) {
                $this->pdo = self::$instance;
            } else { // Should not happen if called from getConnection after successful connection
                try {
                    $this->pdo = new PDO('sqlite:' . $this->dbPath);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $this->pdo->exec('PRAGMA foreign_keys = ON;');
                    self::$instance = $this->pdo;
                } catch (PDOException $e) {
                    die("Database connection failed within initDatabase fallback: " . $e->getMessage());
                }
            }
        }

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_admin BOOLEAN DEFAULT 0 NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Updated 'words' table schema
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS words (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                german_word VARCHAR(255) NOT NULL,
                translation VARCHAR(255) NOT NULL,
                persian_phonetic_pronunciation TEXT NULL,
                word_type_and_gender TEXT NULL,
                word_level TEXT NULL,
                example_german TEXT NULL,
                example_persian_translation TEXT NULL,
                audio_url TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Attempt to drop old audio_filename column
        // This only works in SQLite 3.35.0+
        // We need to check if the column exists before trying to drop it.
        $stmt = $this->pdo->query("PRAGMA table_info(words)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // Get column names
        if (in_array('audio_filename', $columns)) {
            try {
                $this->pdo->exec("ALTER TABLE words DROP COLUMN audio_filename");
            } catch (PDOException $e) {
                // Log or handle error if drop fails for other reasons than "not supported"
                // For now, we proceed, as the new code won't use it.
                // error_log("Note: Could not drop audio_filename column (may not be supported, or other issue): " . $e->getMessage());
            }
        }

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS leitner_cards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                word_id INTEGER NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                box_number INTEGER NOT NULL DEFAULT 0, -- Default to Box 0
                last_reviewed_at TIMESTAMP NULL,
                next_review_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_leitner_cards_user_box ON leitner_cards (user_id, box_number)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_leitner_cards_user_next_review ON leitner_cards (user_id, next_review_at)");
    }

    public static function resetInstance(): void {
        self::$instance = null;
    }
}
