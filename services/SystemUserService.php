<?php

require_once ("entities/SystemUser.php");
interface SystemUserService
{
    public function createSystemUser(string $userName, string $password): int;

    public function logIn(string $userName, string $password) :?int;

    public function changePassword(int $userId, string $newPassword);

    public function createToken(int $userId): ?array;

    public function loginWithToken(string $tokenString) : ?int;
}