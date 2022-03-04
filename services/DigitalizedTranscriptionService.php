<?php

require_once (__DIR__ . "/../entities/DigitalizedTranscription.php");
interface DigitalizedTranscriptionService
{
    public function createDigitalizedTranscription(DigitalizedTranscription $transcription, int $nomenclatorId, int $createdBy): int;

    public function getDigitalizedTranscriptionsOfNomenclator (?array $userInfo, int $nomenclatorId): ?array;

    public function getDigitalizedTranscriptionById(?array $userInfo, $id): ?DigitalizedTranscription;

    public function getEncryptionPairsByTranscriptionId(?array $userInfo, int $id): ?array;

    public function getEncryptionKeyByTranscriptionId(?array $userInfo, int $id): ?array;

    public function getDecryptionKeyByTranscriptionId(?array $userInfo, int $id): ?array;

    public function getAllTranscriptions(?array $userInfo) : ?array;
}