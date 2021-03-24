<?php


class NomenclatorKey {

    public ?int $id;
    public ?string $signature;
    public ?array $images;
    public ?string $completeStructure;
    public ?array $digitalizedTranscriptions;

    public ?string $folder;

    public int $uploadedBy;

    public ?string $date;

    public ?array $keyUsers;

    public ?string $language;


    public static string $table_name = "nomenclatorKeys";

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