<?php

require_once (__DIR__ . "/../entities/NomenclatorFolder.php");

interface NomenclatorFolderService
{
    public function getAllFolders():?array;

    public function folderExists($name) :bool;

    public function getFolderById($id): ?NomenclatorFolder;

}