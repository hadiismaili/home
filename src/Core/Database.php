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
            // Do not call resetInstance here; it's for explicit test control
        } else {
            $this->dbPath = __DIR__ . '/../../database/app.db';
        }
    }

    public function getConnection(): PDO {
        if (self::$instance === null) { // Check static instance first
            try {
                // echo "Attempting DB Connection to: " . $this->dbPath . "\n"; // Debug
                $this->pdo = new PDO('sqlite:' . $this->dbPath);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');

                self::$instance = $this->pdo; // Set static instance
                $this->initDatabase();
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        } else {
            // If static instance exists, ensure this object's pdo property is also set
            $this->pdo = self::$instance;
        }
        return self::$instance; // Return static instance
    }

    private function initDatabase(): void {
        if ($this->pdo === null) {
            // This case should ideally not be hit if getConnection logic is sound
            // and always sets $this->pdo when it sets self::$instance.
            // For robustness, if self::$instance exists (e.g. set by another Database object), use it.
            if (self::$instance !== null) {
                 $this->pdo = self::$instance;
            } else {
                // This is a critical failure state.
                // Attempting to re-establish connection here might be too late or hide issues.
                throw new \LogicException("PDO instance is null in initDatabase and static instance is also null. Connection failed or was reset unexpectedly.");
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
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS words (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                german_word VARCHAR(255) NOT NULL,
                translation VARCHAR(255) NOT NULL,
                audio_filename VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS leitner_cards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                word_id INTEGER NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                box_number INTEGER NOT NULL DEFAULT 1,
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
