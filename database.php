<?php

class Database {
    private $host;
    private $db;
    private $user;
    private $pass;
    private $charset;
    private $dsn;
    private $opt;

    public function __construct($path) {
        $this->loadEnv($path);
        $this->host = $_ENV['DB_HOST'];
        $this->db   = $_ENV['DB_NAME'];
        $this->user = $_ENV['DB_USER'];
        $this->pass = $_ENV['DB_PASS'];
        $this->charset = 'utf8mb4';

        $this->dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $this->opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
    }

    public function connect() {
        return new PDO($this->dsn, $this->user, $this->pass, $this->opt);
    }

    private function loadEnv($path) {
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $_ENV[$name] = $value;
        }
    }
}
