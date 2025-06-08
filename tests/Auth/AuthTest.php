<?php

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\LearningSet;
use App\Core\Database;
use PDO;

class AuthTest extends TestCase
{
    private ?PDO $pdo = null;
    private User $userModel;
    private LearningSet $learningSetModel;

    protected function setUp(): void
    {
        Database::resetInstance();

        $db = new Database();
        $this->pdo = $db->getConnection();

        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM user_leitner_progress");
            $this->pdo->exec("DELETE FROM learning_set_words");
            $this->pdo->exec("DELETE FROM global_word_bank");
            $this->pdo->exec("DELETE FROM users");
            $this->pdo->exec("DELETE FROM learning_sets");
        } else {
            $this->fail("Failed to get PDO connection in setUp.");
        }

        $this->userModel = new User();
        $this->learningSetModel = new LearningSet();

        if (session_status() == PHP_SESSION_ACTIVE) {
             session_unset();
             session_destroy();
        }
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
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
        $this->userModel->create('user2', 'u2@example.com', 'p2', true);
        $this->userModel->create('user3', 'u3@example.com', 'p3');

        $users = $this->userModel->getAllUsers('username', 'ASC');
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

        $result = $this->userModel->deleteById($userId);
        $this->assertTrue($result, "deleteById should return true for successful deletion.");
        $this->assertNull($this->userModel->findById($userId), 'User should be deleted.');
    }

    public function testCountAllUsers()
    {
        $initialCount = $this->userModel->countAll();
        $this->assertEquals(0, $initialCount, "Initial user count should be 0 after setUp.");

        $this->userModel->create('usercount1', 'uc1@example.com', 'p');
        $this->userModel->create('usercount2', 'uc2@example.com', 'p');
        $this->assertEquals(2, $this->userModel->countAll());
    }

    public function testSetAndGetActiveLearningSetId()
    {
        $createUserResult = $this->userModel->create('setuser', 'setuser@example.com', 'pass');
        $this->assertTrue($createUserResult, "Failed to create user for active set test.");
        $user = $this->userModel->findByUsername('setuser');
        $this->assertNotNull($user, "Failed to find user 'setuser'.");
        $userId = $user['id'];

        $adminForSetCreate = $this->userModel->create('adminforset', 'adminforset@example.com', 'pass', true);
        $this->assertTrue($adminForSetCreate);
        $adminUser = $this->userModel->findByUsername('adminforset');
        $this->assertNotNull($adminUser);
        $adminId = $adminUser['id'];

        $mockSetId = $this->learningSetModel->create('Dummy Set 1', null, $adminId);
        $this->assertNotFalse($mockSetId, "Failed to create dummy learning set 1.");
        $mockSetId2 = $this->learningSetModel->create('Dummy Set 2', null, $adminId);
        $this->assertNotFalse($mockSetId2, "Failed to create dummy learning set 2.");

        $this->assertNull($this->userModel->getActiveLearningSetId($userId), 'Active set ID should be null initially.');

        $result = $this->userModel->setActiveLearningSet($userId, $mockSetId);
        $this->assertTrue($result, 'setActiveLearningSet should return true when changing value from null to an ID.');
        $this->assertEquals($mockSetId, $this->userModel->getActiveLearningSetId($userId), 'Active set ID should be updated.');

        $result = $this->userModel->setActiveLearningSet($userId, $mockSetId2);
        $this->assertTrue($result, 'setActiveLearningSet should return true when changing to another ID.');
        $this->assertEquals($mockSetId2, $this->userModel->getActiveLearningSetId($userId), 'Active set ID should now be updated to the second mock ID.');

        $result = $this->userModel->setActiveLearningSet($userId, null);
        $this->assertTrue($result, "setActiveLearningSet should return true when setting to null.");
        $this->assertNull($this->userModel->getActiveLearningSetId($userId), "Active set ID should be null after unsetting.");

        // Test setting to the same value (null again) - focus on state not rowCount
        $this->userModel->setActiveLearningSet($userId, null);
        $this->assertNull($this->userModel->getActiveLearningSetId($userId), 'Active set ID should remain null if set to null again.');

        // Test setting to the same non-null value - focus on state not rowCount
        $this->userModel->setActiveLearningSet($userId, $mockSetId);
        $this->assertEquals($mockSetId, $this->userModel->getActiveLearningSetId($userId), 'Active set ID should be set to mockSetId.');
        $this->userModel->setActiveLearningSet($userId, $mockSetId);
        $this->assertEquals($mockSetId, $this->userModel->getActiveLearningSetId($userId), 'Active set ID should remain mockSetId if set to the same ID again.');
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
