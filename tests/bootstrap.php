<?php

// Set a flag to indicate we are in a testing environment
define('APP_ENV', 'testing');

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Clean up any pre-existing test database file before tests run
$testDbPath = __DIR__ . '/../database/app_test.db';
if (getenv('DB_DATABASE_TEST') && getenv('DB_DATABASE_TEST') !== ':memory:' && file_exists($testDbPath)) {
    // Only unlink if DB_DATABASE_TEST is set, not :memory:, and file exists.
    unlink($testDbPath);
    // echo "Old test database file removed: " . $testDbPath . "\n";
}

// Initialize session for tests if needed
if (session_status() == PHP_SESSION_NONE) {
    // For CLI tests, direct session manipulation is often better.
    // PHPUnit's process isolation handles many session conflicts for controller tests.
    // If specific tests need to start a session, they can do so using @session_start
    // or ensure they are run in a separate process via phpunit.xml configuration.
}

// Environment variables for database are set in phpunit.xml.dist
// The Database class will pick these up.
?>
