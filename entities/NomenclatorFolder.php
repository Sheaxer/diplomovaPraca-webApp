<?php


class NomenclatorFolder
{
    public  $name;
    /** @var Fond */
    public  $fond;
    public  $startDate;
    public  $endDate;

    public  $regions;

    public static  $tableName = "folders";

    public const LIMIT = 15;

}