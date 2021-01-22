<?php


class Nomenklator {

    public ?int $id;
    public ?string $signature;
    public ?array $images;
    public ?string $structure;
    public ?bool $simple;
    public ?bool $homophonic;
    public ?bool $bigrams;
    public ?bool $trigrams;
    public ?bool $codeBook;
    public ?bool $nulls;
    public ?array $digitalizedTranscriptions;

    public ?string $folder;


    public static string $table_name = "nomenklators";

    /*public function  __construct(?int $id = null, ?string $signature = null, ?string $structure = null,
                                 ?array $images = null, ?bool $simple = null, ?bool $homophonic = null,
                                 ?bool $bigrams = null, ?bool $trigrams = null, ?bool $codeBook = null,
                                 ?bool $nulls = null, ?array $digitalizedTranscriptions = null, ?string $folder = null)
    {
        $this->id = $id;
        $this->signature = $signature;
        $this->images = $images;
        $this->structure = $structure;
        $this->simple=$simple;
        $this->homophonic = $homophonic;
        $this->bigrams = $bigrams;
        $this->trigrams = $trigrams;
        $this->codeBook = $codeBook;
        $this->nulls = $nulls;
        $this->digitalizedTranscriptions = $digitalizedTranscriptions;
        $this->folder = $folder;
    }*/


}