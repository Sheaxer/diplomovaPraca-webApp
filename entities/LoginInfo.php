<?php


class LoginInfo
{
    public static  $table_name = "logins";

    private static  $hour_number = 48;


    public static function getHoursToExpire():int
    {
        return LoginInfo::$hour_number;
    }

    public  $userId;
    public $hash;
    public $expiresAt;
    public  $loginDate;




}