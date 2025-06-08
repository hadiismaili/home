<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private string $dbPath;

    public function __construct() {
        if (getenv('APP_ENV') === 'testing') {
            $dbName = getenv('DB_DATABASE_TEST') ?: ':memory:';
            if ($dbName === ':memory:') {
                $this->dbPath = ':memory:';
            } else {
                $this->dbPath = __DIR__ . '/../../' . $dbName;
            }
            // DO NOT call self::resetInstance() here. It should be controlled by test setUp/tearDown.
        } else {
            $this->dbPath = __DIR__ . '/../../database/app.db';
        }
    }

    public static function resetInstance(): void {
        // This allows tests to explicitly reset the DB state for :memory:
        if (self::$instance !== null) {
            // Potentially close the connection if it's a file-based DB to release locks,
            // but for :memory:, just nullifying is enough as the DB disappears when connection is lost.
            // self::$instance = null; // For PDO, simply nullifying the static var is enough to ensure a new connection is made.
        }
        self::$instance = null;
    }

    public function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                if ($this->dbPath !== ':memory:') {
                    $dbDir = dirname($this->dbPath);
                    if (!is_dir($dbDir)) {
                        mkdir($dbDir, 0755, true);
                    }
                }

                self::$instance = new PDO('sqlite:' . $this->dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->exec('PRAGMA foreign_keys = ON;');

                $this->initDatabase();
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private function initDatabase(): void {
        $pdo = self::$instance;
        if (!$pdo) {
            throw new \LogicException("PDO instance is null in initDatabase. Connection might have failed.");
        }
        // Schema definition remains the same...
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("
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
        $pdo->exec("
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
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leitner_cards_user_box ON leitner_cards (user_id, box_number)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leitner_cards_user_next_review ON leitner_cards (user_id, next_review_at)");
    }
}
