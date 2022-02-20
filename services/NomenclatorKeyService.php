<?php

require_once ("entities/NomenclatorKey.php");
interface NomenclatorKeyService
{
    public function createNomenclatorKey(int $userId, NomenclatorKey $nomenclator): ?int;

    public function getNomenclatorKeyById(?array $userInfo, int $id): ?NomenclatorKey;

    public function getNomenclatorKeyBySignature(?array $userInfo, string $signature): ?NomenclatorKey;

    public function getNomenklatorKeysByAttributes(?array $userInfo ,?array $folders = null, ?array $structures = null): ?array;

    public function nomenclatorKeyExistsById($keyId) :bool;

    public function nomenclatorKeyExistsBySignature($signature):bool;

    public function updateNomenclatorKeyState(array $userInfo, $state, $note, ?int $nomenclatorId, ?int $stateId,  ): bool;

    public function getNomenclatorKeyState(?array $userInfo, $nomenclatorId, $stateId): ?NomenclatorKeyState;


}