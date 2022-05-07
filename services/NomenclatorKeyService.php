<?php

require_once ( __DIR__ .  "/../entities/NomenclatorKey.php");
interface NomenclatorKeyService
{
    public function createNomenclatorKey(int $userId, NomenclatorKey $nomenclator): ?array;

    public function getNomenclatorKeyById(?array $userInfo, int $id): ?NomenclatorKey;

    public function getNomenclatorKeyBySignature(?array $userInfo, string $signature): ?NomenclatorKey;

    public function getNomenklatorKeysByAttributes(?array $userInfo, $limit, $page, ?array $folders = null, ?array $structures = null, bool $myKeys = false, string $state = null): ?array;

    public function nomenclatorKeyExistsById(?array $userInfo, $keyId) :bool;

    public function nomenclatorKeyExistsBySignature($signature):bool;

    public function updateNomenclatorKeyState(array $userInfo, $state, $note, ?int $nomenclatorId, ?int $stateId): bool;

    public function getNomenclatorKeyState(?array $userInfo, $nomenclatorId, $stateId): ?NomenclatorKeyState;

    public function updateNomenclatorKey(array $userInfo, NomenclatorKey $nomenclatorKey);

    public function addKeyUsersToNomenclatorKey(array $userInfo, int $nomenclatorKeyId, array $users);

    public function removeKeyUsersFromNomenclatorKey(array $userInfo, int $nomenclatorKeyId, array $users);

}