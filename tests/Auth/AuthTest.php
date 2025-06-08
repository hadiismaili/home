<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Core\Database;
use PDO;

class AuthTest extends TestCase
{
    private ?PDO $pdo = null;
    private User $userModel;

    protected function setUp(): void
    {
        Database::resetInstance();

        $db = new Database();
        $this->pdo = $db->getConnection();

        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM users"); // Clear users for each test
            $this->pdo->exec("DELETE FROM words"); // Clear related data that might be affected by user tests (cascade)
            $this->pdo->exec("DELETE FROM leitner_cards"); // Clear related data
        } else {
            $this->fail("Failed to get PDO connection in setUp.");
        }

        $this->userModel = new User();

        if (session_status() == PHP_SESSION_ACTIVE) {
             session_unset();
             session_destroy();
        }
        $_SESSION = [];
    }

    public function testUserCanBeRegistered()
    {
        $username = 'testuser';
        $email = 'test@example.com';
        $password = 'password123';

        $result = $this->userModel->create($username, $email, $password);
        $this->assertTrue($result, "User creation should return true.");

        $user = $this->userModel->findByUsername($username);
        $this->assertNotNull($user, "User should be found in database after registration.");
        $this->assertEquals($email, $user['email']);
        $this->assertTrue(password_verify($password, $user['password_hash']), "Password hash should match.");
    }

    public function testCannotRegisterWithDuplicateUsername()
    {
        $this->userModel->create('existinguser', 'email1@example.com', 'pass1');
        $result = $this->userModel->create('existinguser', 'email2@example.com', 'pass2');
        $this->assertFalse($result, "Should not register with duplicate username.");
    }

    public function testCannotRegisterWithDuplicateEmail()
    {
        $this->userModel->create('user1', 'existing@example.com', 'pass1');
        $result = $this->userModel->create('user2', 'existing@example.com', 'pass2');
        $this->assertFalse($result, "Should not register with duplicate email.");
    }

    public function testUserCanLoginWithCorrectCredentials()
    {
        $username = 'loginuser';
        $email = 'login@example.com';
        $password = 'securepass';
        $this->userModel->create($username, $email, $password);

        $user = $this->userModel->findByUsername($username);
        $this->assertNotNull($user);
        $this->assertTrue(password_verify($password, $user['password_hash']));
    }

    public function testUserCannotLoginWithIncorrectPassword()
    {
        $username = 'loginuserwp';
        $email = 'loginwp@example.com';
        $password = 'securepass';
        $this->userModel->create($username, $email, $password);

        $user = $this->userModel->findByUsername($username);
        $this->assertNotNull($user);
        $this->assertFalse(password_verify('wrongpassword', $user['password_hash']));
    }

    public function testGetAllUsers()
    {
        $this->userModel->create('user1', 'u1@example.com', 'p1');
        $this->userModel->create('user2', 'u2@example.com', 'p2', true); // Admin user
        $this->userModel->create('user3', 'u3@example.com', 'p3');

        $users = $this->userModel->getAllUsers('username', 'ASC'); // Test ordering
        $this->assertCount(3, $users);
        $this->assertEquals('user1', $users[0]['username']);
        $this->assertEquals('user2', $users[1]['username']);
        $this->assertEquals(1, (int)$users[1]['is_admin']);
        $this->assertEquals('user3', $users[2]['username']);
    }

    public function testSetAdminStatus()
    {
        $createResult = $this->userModel->create('testadmin', 'ta@example.com', 'pw');
        $this->assertTrue($createResult, "Test setup: Failed to create user for testSetAdminStatus.");
        $user = $this->userModel->findByUsername('testadmin');
        $this->assertNotNull($user, "Test setup: User 'testadmin' not found after creation.");
        $userId = $user['id'];

        $this->assertFalse((bool)$user['is_admin'], 'User should not be admin initially.');

        $result = $this->userModel->setAdminStatus($userId, true);
        $this->assertTrue($result, 'setAdminStatus should return true on success for making admin.');
        $updatedUser = $this->userModel->findById($userId);
        $this->assertTrue((bool)$updatedUser['is_admin'], 'User should be admin after setAdminStatus(true).');
        $this->assertTrue($this->userModel->isAdmin($userId), 'isAdmin should return true for admin user.');

        $result = $this->userModel->setAdminStatus($userId, false);
        $this->assertTrue($result, 'setAdminStatus should return true on success for revoking admin.');
        $updatedUser = $this->userModel->findById($userId);
        $this->assertFalse((bool)$updatedUser['is_admin'], 'User should not be admin after setAdminStatus(false).');
        $this->assertFalse($this->userModel->isAdmin($userId), 'isAdmin should return false for non-admin user.');
    }

    public function testDeleteUserById()
    {
        $createResult = $this->userModel->create('todelete', 'del@example.com', 'pw');
        $this->assertTrue($createResult, "Test setup: Failed to create user for testDeleteUserById.");
        $user = $this->userModel->findByUsername('todelete');
        $this->assertNotNull($user, "Test setup: User 'todelete' not found after creation.");
        $userId = $user['id'];

        $wordModel = new \App\Models\Word();
        $cardModel = new \App\Models\LeitnerCard();

        $wordId1 = $wordModel->create($userId, 'Wort1Del', 'Word1Del');
        $this->assertNotFalse($wordId1, "Failed to create word1 for cascade delete test.");
        if ($wordId1) $cardModel->create($wordId1, $userId); // Only create card if word was created

        $wordId2 = $wordModel->create($userId, 'Wort2Del', 'Word2Del');
        $this->assertNotFalse($wordId2, "Failed to create word2 for cascade delete test.");
        if ($wordId2) $cardModel->create($wordId2, $userId); // Only create card if word was created

        if ($wordId1) $this->assertNotNull($wordModel->findById($wordId1, $userId), "Word1 should exist before user deletion.");
        if ($wordId1) $this->assertNotNull($cardModel->findByWordId($wordId1, $userId), "Card for Word1 should exist before user deletion.");

        $result = $this->userModel->deleteById($userId);
        $this->assertTrue($result, "deleteById should return true for successful deletion.");
        $this->assertNull($this->userModel->findById($userId), 'User should be deleted.');

        if ($wordId1) $this->assertNull($wordModel->findById($wordId1, $userId), 'Word1 should be deleted by cascade.');
        if ($wordId1) $this->assertNull($cardModel->findByWordId($wordId1, $userId), 'Card for Word1 should be deleted by cascade.');
        if ($wordId2) $this->assertNull($wordModel->findById($wordId2, $userId), 'Word2 should be deleted by cascade.');
        if ($wordId2) $this->assertNull($cardModel->findByWordId($wordId2, $userId), 'Card for Word2 should be deleted by cascade.');
    }

    public function testCountAllUsers()
    {
        $initialCount = $this->userModel->countAll();

        $this->userModel->create('usercount1', 'uc1@example.com', 'p');
        $this->userModel->create('usercount2', 'uc2@example.com', 'p');
        $this->assertEquals($initialCount + 2, $this->userModel->countAll());
    }

    protected function tearDown(): void
    {
        Database::resetInstance();
        $this->pdo = null;

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    }
}
