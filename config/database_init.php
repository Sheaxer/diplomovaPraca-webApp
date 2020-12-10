<?php
class Database{
	private string $host = "localhost";
	private string $db_name = "nomenklature_db";
	private string $username = "";
	private string $password = "";
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


