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

    public function create(string $username, string $email, string $password): bool {
        // Check if user already exists by username or email
        // Although UNIQUE constraints handle this at DB level, this provides a cleaner check first.
        if ($this->findByUsername($username) || $this->findByEmail($email)) {
            // error_log("User model: Attempt to create user that already exists (username: $username, email: $email)");
            return false;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);

        if (!$stmt->execute()) {
            // error_log("User model: stmt->execute() failed for user: $username");
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
}
