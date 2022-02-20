<?php

require_once (__DIR__ . "/../entities/DigitalizedTranscription.php");
interface DigitalizedTranscriptionService
{
    public function createDigitalizedTranscription(DigitalizedTranscription $transcription, int $nomenclatorId, int $createdBy): int;

    public function getDigitalizedTranscriptionsOfNomenclator (?array $userInfo, int $nomenclatorId): ?array;

    public function getDigitalizedTranscriptionById($id): ?DigitalizedTranscription;

    public function getEncryptionPairsByTranscriptionId(int $id): ?array;

    public function getEncryptionKeyByTranscriptionId(int $id): ?array;

    public function getDecryptionKeyByTranscriptionId(int $id): ?array;

    public function getAllTranscriptions() : ?array;
}