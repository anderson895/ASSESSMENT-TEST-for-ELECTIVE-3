<?php
/**
 * Database.php
 * -----------------------------------------------------------------
 * Ito ang class na nag-uugnay sa PHP at sa MySQL database.
 * Ginagamit natin ang PDO dahil ito ang ligtas na paraan
 * (protektado laban sa SQL injection).
 * -----------------------------------------------------------------
 */
class Database
{
    // ---- Mga setting ng koneksyon (palitan kung iba ang XAMPP mo) ----
    private $host     = 'localhost';
    private $dbName   = 'online_billing';
    private $username = 'root';
    private $password = '';        // walang password ang default na XAMPP

    // Dito itatago ang koneksyon para hindi paulit-ulit gumawa ng bago.
    private $connection = null;

    /**
     * Ibinabalik ang koneksyon sa database.
     */
    public function connect()
    {
        // Kung may koneksyon na, gamitin na lang ulit iyon.
        if ($this->connection != null) {
            return $this->connection;
        }

        // Ito ang "address" ng database na kokonektahan natin.
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbName . ";charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password);

            // Kapag may mali, magpapakita ng error (hindi tahimik lang).
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Ang resulta ng query ay gagawing associative array: $row['column_name']
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Kapag hindi makakonekta, ipaalam kung bakit.
            throw new RuntimeException('Hindi makakonekta sa database: ' . $e->getMessage());
        }

        return $this->connection;
    }
}
