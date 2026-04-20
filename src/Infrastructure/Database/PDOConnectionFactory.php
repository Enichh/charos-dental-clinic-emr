<?php

namespace CharosEMR\Infrastructure\Database;

use PDO;

class PDOConnectionFactory
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private array $options;

    public function __construct(
        string $host = '127.0.0.1',
        string $database = 'charos_emr',
        string $username = 'root',
        string $password = '',
        array $options = []
    ) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $options);
    }

    public function create(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
        return new PDO($dsn, $this->username, $this->password, $this->options);
    }
}
