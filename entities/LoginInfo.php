<?php


class LoginInfo
{
    public static string $table_name = "logins";

    private static int $hour_number = 48;


    public static function getHoursToExpire():int
    {
        return LoginInfo::$hour_number;
    }




}