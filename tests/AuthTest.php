<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

class AuthTest extends TestCase
{
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/kasir_test.db';
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        putenv('DB_PATH=' . $this->dbPath);
        $db = new Database();
        $db->getConnection();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testLoginWithValidAdminCredentials(): void
    {
        $auth = new Auth();
        $this->assertTrue($auth->login('admin', 'password'));
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $auth = new Auth();
        $this->assertFalse($auth->login('admin', 'wrongpassword'));
    }
}
