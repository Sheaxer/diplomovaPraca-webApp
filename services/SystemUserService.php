<?php

require_once (__DIR__ . "/../entities/SystemUser.php");
interface SystemUserService
{
    public function createSystemUser(string $userName, string $password, bool $isAdmin): int;

    public function logIn(string $userName, string $password) :?array;

    public function changePassword(int $userId, string $newPassword);

    public function createToken(int $userId): ?array;

    public function loginWithToken(string $tokenString);

    public function getUsernameById($id): ?string;
}