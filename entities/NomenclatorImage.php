<?php


class NomenclatorImage
{
    public static string $tableName = "images";

    public static string $uploadFolder = "images/";

    public string $url;

    public bool $isLocal = true;

    public ?string $structure;


}