<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Core\Database;
use PDO;

class AuthTest extends TestCase
{
    private ?PDO $pdo = null; // Nullable for safety, should be set in setUp
    private User $userModel;

    protected function setUp(): void
    {
        // Ensure we are using the test database defined in phpunit.xml.dist
        // The Database class constructor now handles APP_ENV 'testing'.
        // Setting instance to null before new Database() ensures a fresh connection for each test.
        Database::resetInstance(); // Add a static method to Database class to reset its static instance

        $db = new Database(); // This will use test DB settings due to phpunit.xml.dist and modified constructor
        $this->pdo = $db->getConnection(); // This also calls initDatabase()

        // Clear users table before each test for isolation
        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM users");
        } else {
            $this->fail("Failed to get PDO connection in setUp.");
        }

        $this->userModel = new User();

        // Manage session for tests that might use it (though these model tests don't directly).
        // Best practice: clear/manage \$_SESSION if controller tests were here.
        if (session_status() == PHP_SESSION_ACTIVE) { // Check if active before trying to destroy
             session_unset();
             session_destroy(); // Destroy any pre-existing session
        }
        // Start a new session only if absolutely needed and manage carefully.
        // For model tests, it's usually not needed.
        // If a test needs session, it can explicitly start it.
        $_SESSION = []; // Ensure it's clean if used
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

    protected function tearDown(): void
    {
        // Reset instance after each test to ensure next test gets a fresh start,
        // especially important for in-memory DB that gets wiped when connection is closed.
        Database::resetInstance();
        $this->pdo = null; // Release PDO instance

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    }
}
