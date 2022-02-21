<?php


class POSTDatabaseConfig
{
    private  $host = "localhost";
    private  $db_name = "nomenclators";
    private  $username = "root";
    private  $password = "";
    private $port = "3306";
    public  $conn;

    public function getConnection(): ?PDO
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=". $this->host . ";dbname=" . $this->db_name . ";port=" . $this->port,
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
    private  $host = "localhost";
    private  $db_name = "nomenclators";
    private  $username = "root";
    private  $password = "";
    private $port = "3306";
    public  $conn;

    public function getConnection(): ?PDO
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=". $this->host . ";dbname=" . $this->db_name . ";port=" . $this->port,
                $this->username,$this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}