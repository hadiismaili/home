<?php

namespace App\Models;

use PDO;
use App\Core\Database;

class User {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $username, string $email, string $password, bool $isAdmin = false): bool {
        if ($this->findByUsername($username) || $this->findByEmail($email)) {
            return false;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // active_learning_set_id will default to NULL in the database
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (:username, :email, :password_hash, :is_admin)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $isAdminInt = (int)$isAdmin;
        $stmt->bindParam(':is_admin', $isAdminInt, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }
        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function setAdminStatus(int $userId, bool $isAdmin): bool {
        $sql = "UPDATE users SET is_admin = :is_admin WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $isAdminInt = (int)$isAdmin;
        $stmt->bindParam(':is_admin', $isAdminInt, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function isAdmin(int $userId): bool {
        $user = $this->findById($userId);
        return $user ? (bool)$user['is_admin'] : false;
    }

    public function getDbConnection(): PDO {
        return $this->db;
    }

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return (int)$stmt->fetchColumn();
    }

    public function getAllUsers(string $orderBy = 'created_at', string $orderDir = 'DESC'): array {
        $allowedOrderBy = ['id', 'username', 'email', 'is_admin', 'created_at', 'active_learning_set_id']; // Added active_learning_set_id
        if (!in_array(strtolower($orderBy), $allowedOrderBy, true)) {
            $orderBy = 'created_at';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT id, username, email, is_admin, created_at, active_learning_set_id FROM users ORDER BY " . $orderBy . " " . $orderDir;
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function deleteById(int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function setActiveLearningSet(int $userId, ?int $learningSetId): bool {
        $sql = "UPDATE users SET active_learning_set_id = :set_id WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':set_id', $learningSetId, $learningSetId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() > 0; // Returns true if update was successful and affected rows
        }
        return false;
    }

    public function getActiveLearningSetId(int $userId): ?int {
        $user = $this->findById($userId);
        if ($user && isset($user['active_learning_set_id']) && $user['active_learning_set_id'] !== null && $user['active_learning_set_id'] !== 0) {
            return (int)$user['active_learning_set_id'];
        }
        return null;
    }
}
