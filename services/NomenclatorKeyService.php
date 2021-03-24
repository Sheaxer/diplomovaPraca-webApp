<?php

require_once ("entities/NomenclatorKey.php");
interface NomenclatorKeyService
{
    public function createNomenclatorKey(int $userId, NomenclatorKey $nomenclator): ?int;

    public function getNomenclatorKeyById(int $id): ?NomenclatorKey;

    public function getNomenclatorKeyBySignature(string $signature): ?NomenclatorKey;

    public function getNomenklatorKeysByAttributes(?array $folders = null, ?array $structures = null): ?array;

    public function nomenclatorKeyExistsById($keyId) :bool;

    public function nomenclatorKeyExistsBySignature($signature):bool;


}