<?php

require_once (__DIR__ . "/../entities/NomenclatorFolder.php");

interface NomenclatorFolderService
{
    public function getAllFolders($limit, $page): ?array;

    public function folderExists($name) :bool;

    public function getFolderByName($name);

    public function createFolder(NomenclatorFolder $folder);

}