<?php


class Nomenklature {

    public int $id;
    public string $signature;
    public array $images;
    public ?string $description;
    public ?bool $simple;
    public ?bool $homophonic;
    public ?bool $bigrams;
    public ?bool $trigrams;
    public ?bool $codeBook;
    public ?bool $nulls;

    private ?PDO $conn;
    private string $table_name = "nomenklatures";

    function  __construct(PDO $db)
    {
        $this->conn=$db;
    }
}