<?php
/**
 * Database.php
 * Object-Oriented PDO connection handler for the Online Billing System.
 * Adjust the credentials below to match your XAMPP MySQL setup.
 */
class Database
{
    private string $host = 'localhost';
    private string $dbName = 'online_billing';
    private string $username = 'root';
    private string $password = '';      // default XAMPP MySQL password is empty
    private string $charset = 'utf8mb4';

    private ?PDO $connection = null;

    /**
     * Return a singleton-style PDO connection.
     */
    public function connect(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return $this->connection;
    }
}
