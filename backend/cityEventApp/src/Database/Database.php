<?php

namespace App\Database;

use PDO;
use PDOStatement;

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $host = 'localhost';
        $db = 'eventsapp';
        $user = 'root';
        $pass = '@Database3!';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function prepare($sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function execute($sql): int
    {
        return $this->pdo->exec($sql);
    }
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}