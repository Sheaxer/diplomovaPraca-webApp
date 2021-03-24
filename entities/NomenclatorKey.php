<?php


class NomenclatorKey {

    public  $id;
    public  $signature;
    public $images;
    public  $completeStructure;
    public  $digitalizedTranscriptions;

    public  $folder;

    public  $uploadedBy;

    public  $date;

    public  $keyUsers;

    public  $language;


    public static $table_name = "nomenclatorKeys";

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