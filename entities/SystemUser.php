<?php


class SystemUser
{
    public  $username;
    public  $passwordHash;
    public  $id;
    public  $isAdmin;
    public $approved;

    public static  $tableName = "systemusers";


}
