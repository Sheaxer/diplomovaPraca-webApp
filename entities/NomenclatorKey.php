<?php


class NomenclatorKey {

    const LIMIT = 15;

    public  $id;
    public  $signature;
    public  $images;
    public  $completeStructure;
    public  $digitalizedTranscriptions;

    public  $folder;

    //public  $uploadedBy;

    //public  $date;

    public $usedChars;

    public $cipherType;

    public $keyType;

    public $usedFrom;

    public $usedTo;

    public $usedAround;

    public $groupId;

    public  $keyUsers;

    public  $language;

    //public $stateId;

    /** @var Place */
    public $placeOfCreation = null;

    public $placeOfCreationId;

    /**  @var NomenclatorKeyState|null */
    public $state = null;


    public static $table_name = "nomenclatorkeys";

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

    public static function createFromArray(array $data)
    {
        $key = new static();
        $key->cipherType = $data['cipherType'] ?? null;
        $key->completeStructure = $data['completeStructure'] ?? null;
        $key->groupId = $data['groupId'] ?? null;
        $key->id = $data['id'] ?? null;
        $key->keyType = $data['keyType'] ?? null;
        $key->language = $data['language'] ?? null;
        $key->signature = $data['signature'] ?? '';
       
        $key->usedAround = $data['usedAround'] ?? null;
        $key->usedChars = $data['usedChars'];
        $key->usedFrom = $data['usedFrom'] ?? null;
        $key->usedTo = $data['usedTo'] ?? null;

        //$key->stateId = $data['stateId'] ?? null;
        if (isset($data['stateId']) && $data['stateId']) {
            $key->state = new NomenclatorKeyState();
            $key->state->id = $data['stateId'] ?? null;
            $key->state->createdAt = $data['createdAt'] ?? null;
            $key->state->createdById = $data['createdBy'] ?? null;
            $key->state->note = $data['note'] ?? null;
            $key->state->updatedAt = $data['updatedAt'] ?? null;
            $key->state->state = $data['state'] ?? null;
            $key->state->nomenclatorKeyId = $data['id'] ?? null;
        }
        return $key;

    }


}