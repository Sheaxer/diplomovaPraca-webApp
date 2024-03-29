<?php
require_once (__DIR__ . "/../entities/KeyUser.php");

interface KeyUserService
{
    public function createKeyUser(KeyUser $user, $doTransaction): ?int;
    public function assignKeyUserToNomenclatorKey(int $id, int $nomenclatorKeyId, $isMainUser);
    public function getKeyUserByName(string $name): ?KeyUser;
    public function getKeyUserById(int $id): ?KeyUser;

    public function getKeyUsersByNomenclatorKeyId(int $id): ?array;

    public function getAllKeyUsers($page, $limit) : ?array;
}