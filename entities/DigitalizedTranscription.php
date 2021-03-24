<?php


class DigitalizedTranscription
{
    public ?int $id;
    public ?int $nomenclatorKeyId;
    public ?string $digitalizationVersion;
    public ?string $note;
    public ?string $digitalizationDate;
    public int $createdBy;
    public ?array $encryptionPairs;

    public static string $tableName = "digitalizedTranscriptions";

    /*public function __construct(?int $id=null, ?int $nomenklatorId=null,?string $digitalizationVersion = null,
    ?string $note=null, ?DateTime $digitalizationDate = null, ?int $createdBy = null, ?array $encryptionPairs = null)
    {
        $this->id = $id;
        $this->nomenklatorId=$nomenklatorId;
        $this->digitalizationVersion = $digitalizationVersion;
        $this->note=$note;
        $this->digitalizationDate = $digitalizationDate;
        $this->createdBy=$createdBy;
        $this->encryptionPairs = $encryptionPairs;
    }*/
}