<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_NAME') ?: 'clinic_db';
            $username = getenv('DB_USER') ?: 'clinic_user';
            $password = getenv('DB_PASS') ?: 'clinic_pass';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Retry logic in case MySQL container is still initializing
            $attempts = 0;
            $maxAttempts = 5;
            while ($attempts < $maxAttempts) {
                try {
                    self::$instance = new PDO($dsn, $username, $password, $options);
                    break;
                } catch (PDOException $e) {
                    $attempts++;
                    if ($attempts >= $maxAttempts) {
                        throw new PDOException("Database connection failed after {$maxAttempts} attempts: " . $e->getMessage(), (int)$e->getCode());
                    }
                    usleep(500000); // 0.5s backoff
                }
            }
        }

        return self::$instance;
    }
}
