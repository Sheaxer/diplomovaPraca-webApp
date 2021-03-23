<?php


class POSTDatabaseConfig
{
    private string $host = "localhost";
    private string $db_name = "nomenclators";
    private string $username = "test";
    private string $password = "test";
    public ?PDO $conn;

    public function getConnection(): ?PDO
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=". $this->host . ";dbname=" . $this->db_name,
                $this->username,$this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

class GETDatabaseConfig
{
    private string $host = "localhost";
    private string $db_name = "nomenclators";
    private string $username = "test";
    private string $password = "test";
    public ?PDO $conn;

    public function getConnection(): ?PDO
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=". $this->host . ";dbname=" . $this->db_name,
                $this->username,$this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}