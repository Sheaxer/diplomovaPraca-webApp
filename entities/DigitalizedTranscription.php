<?php


class DigitalizedTranscription
{
    public  $id;
    public  $nomenclatorKeyId;
    public  $digitalizationVersion;
    public  $note;
    public  $digitalizationDate;
    public  $createdBy;
    public  $encryptionPairs;

    public static $tableName = "digitalizedTranscriptions";

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