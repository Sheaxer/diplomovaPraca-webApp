<?php


class User
{
    public string $username;
    public string $passwordHash;
    public int $id;

    public static string $tableName = "users";


}