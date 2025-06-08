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
                die("Database connection failed: " . $e->getMessage());
            }
        } else {
            $this->pdo = self::$instance;
            if ($this->pdo) $this->pdo->exec('PRAGMA foreign_keys = ON;'); // Ensure for existing connections
        }
        return self::$instance;
    }

    private function initDatabase(): void {
        if ($this->pdo === null) {
            if (self::$instance) {
                $this->pdo = self::$instance;
            } else {
                try {
                    $this->pdo = new PDO('sqlite:' . $this->dbPath);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance = $this->pdo;
                } catch (PDOException $e) {
                    die("Database connection failed critically in initDatabase fallback: " . $e->getMessage());
                }
            }
        }
        $this->pdo->exec('PRAGMA foreign_keys = ON;');

        // Drop old tables first, in order that respects FKs if they were linked differently before
        // (e.g., if leitner_cards had an FK to words that wasn't CASCADE)
        $this->pdo->exec("DROP TABLE IF EXISTS leitner_cards"); // Old table
        $this->pdo->exec("DROP TABLE IF EXISTS words");       // Old table

        // New tables will be created after this. User table is modified.

        // --- learning_sets Table (Create before users references it, if not using ALTER) ---
        // However, users table is created with IF NOT EXISTS, so it's fine.
        // The ALTER TABLE approach is for existing databases.
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS learning_sets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NULL,
                admin_id INTEGER NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        $this->pdo->exec("
            CREATE TRIGGER IF NOT EXISTS update_learning_sets_updated_at
            AFTER UPDATE ON learning_sets
            FOR EACH ROW
            BEGIN
                UPDATE learning_sets SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
            END;
        ");

        // --- users Table (Add active_learning_set_id) ---
        // Create table if it doesn't exist, including the new FK
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_admin BOOLEAN DEFAULT 0 NOT NULL,
                active_learning_set_id INTEGER NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (active_learning_set_id) REFERENCES learning_sets(id) ON DELETE SET NULL
            )
        ");
        // Attempt to add column if table already existed without it
        $stmt = $this->pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('active_learning_set_id', $columns)) {
            try {
                // Note: Default FK definition might be complex with ALTER TABLE in older SQLite.
                // Simpler to add column then add FK if needed, but direct add with FK reference is cleaner if supported.
                $this->pdo->exec("ALTER TABLE users ADD COLUMN active_learning_set_id INTEGER NULL REFERENCES learning_sets(id) ON DELETE SET NULL");
            } catch (PDOException $e) {
                // error_log("Notice: Could not add active_learning_set_id to users, might exist or other issue: " . $e->getMessage());
            }
        }


        // --- global_word_bank Table ---
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS global_word_bank (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                german_word TEXT NOT NULL UNIQUE,
                translation TEXT NOT NULL,
                persian_phonetic_pronunciation TEXT NULL,
                word_type TEXT NULL,
                word_gender TEXT NULL,
                word_level TEXT NULL,
                example_german TEXT NULL,
                example_persian_translation TEXT NULL,
                audio_url TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->pdo->exec("
            CREATE TRIGGER IF NOT EXISTS update_global_word_bank_updated_at
            AFTER UPDATE ON global_word_bank
            FOR EACH ROW
            BEGIN
                UPDATE global_word_bank SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
            END;
        ");

        // --- learning_set_words (Junction Table) ---
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS learning_set_words (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                learning_set_id INTEGER NOT NULL,
                global_word_id INTEGER NOT NULL,
                FOREIGN KEY (learning_set_id) REFERENCES learning_sets(id) ON DELETE CASCADE,
                FOREIGN KEY (global_word_id) REFERENCES global_word_bank(id) ON DELETE CASCADE,
                UNIQUE (learning_set_id, global_word_id)
            )
        ");

        // --- user_leitner_progress Table ---
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_leitner_progress (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                global_word_id INTEGER NOT NULL,
                learning_set_id INTEGER NOT NULL,
                box_number INTEGER NOT NULL DEFAULT 0,
                last_reviewed_at TIMESTAMP NULL,
                next_review_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (global_word_id) REFERENCES global_word_bank(id) ON DELETE CASCADE,
                FOREIGN KEY (learning_set_id) REFERENCES learning_sets(id) ON DELETE CASCADE,
                UNIQUE (user_id, global_word_id, learning_set_id)
            )
        ");
        $this->pdo->exec("
            CREATE TRIGGER IF NOT EXISTS update_user_leitner_progress_updated_at
            AFTER UPDATE ON user_leitner_progress
            FOR EACH ROW
            BEGIN
                UPDATE user_leitner_progress SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
            END;
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_ulp_user_set_next_review ON user_leitner_progress (user_id, learning_set_id, next_review_at)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_ulp_user_set_box ON user_leitner_progress (user_id, learning_set_id, box_number)");
    }

    public static function resetInstance(): void {
        self::$instance = null;
    }
}
