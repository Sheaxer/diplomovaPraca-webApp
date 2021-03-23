<?php


interface NomenclatorFolderService
{
    public function getAllFolders():?array;

    public function folderExists($name) :bool;

}