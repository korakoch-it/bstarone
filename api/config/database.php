<?php
class Database
{
    protected $pdo = null;
    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        if ($this->pdo === null) {
            $host   = getenv('DB_HOST') ?: 'db';
            $port   = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'clinic_db';
            $user   = getenv('DB_USER') ?: 'clinic_user';
            $pass   = getenv('DB_PASS') ?: 'clinic_pass';

            try {
                $this->pdo = new PDO(
                    "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}
?>