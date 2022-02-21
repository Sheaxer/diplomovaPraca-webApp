<?php
require_once (__DIR__ . "/../services/DigitalizedTranscriptionService.php");
require_once (__DIR__ . "/../services/mysql/DigitalizedTranscriptionServiceImpl.php");

require_once (__DIR__ ."/../services/KeyUserService.php");
require_once (__DIR__ . "/../services/mysql/KeyUserServiceImpl.php");

require_once (__DIR__ ."/../services/NomenclatorFolderService.php");
require_once (__DIR__ ."/../services/mysql/NomenclatorFolderServiceImpl.php");

require_once (__DIR__ ."/../services/NomenclatorImageService.php");
require_once (__DIR__ . "/../services/mysql/NomenclatorImageServiceImpl.php");

require_once (__DIR__ ."/../services/NomenclatorKeyService.php");
require_once (__DIR__ ."/../services/mysql/NomenclatorKeyServiceImpl.php");

require_once (__DIR__ . "/DatabaseConfig.php");

require_once (__DIR__ . "/../services/SystemUserService.php");
require_once (__DIR__ . "/../services/mysql/SystemUserServiceImpl.php");

require_once (__DIR__ . "/../services/NomenclatorPlaceService.php");
require_once (__DIR__ . "/../services/mysql/NomenclatorPlaceServiceImpl.php");

function GETConnection(): ?PDO
{
    $conf = new GETDatabaseConfig();
    return $conf->getConnection();
}

function POSTConnection(): ?PDO
{
    $conf = new POSTDatabaseConfig();
    return $conf->getConnection();
}


function GETDigitalizedTranscriptionService() : ?DigitalizedTranscriptionService
{
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new DigitalizedTranscriptionServiceImpl($conn);
}

function POSTDigitalizedTranscriptionService() : ?DigitalizedTranscriptionService
{
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new DigitalizedTranscriptionServiceImpl($conn);
}

function GETKeyUserService() : ?KeyUserService
{
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new KeyUserServiceImpl($conn);
}

function POSTKeyUserService() : ?KeyUserService
{
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new KeyUserServiceImpl($conn);
}

function GETNomenclatorFolderService() : ?NomenclatorFolderService
{
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new NomenclatorFolderServiceImpl($conn);
}

function POSTNomenclatorFolderService() : ?NomenclatorFolderService
{
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new NomenclatorFolderServiceImpl($conn);
}

function GETNomenclatorImageService() : ?NomenclatorImageService
{
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new NomenclatorImageServiceImpl($conn);
}

function POSTNomenclatorImageService() : ?NomenclatorImageService
{
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new NomenclatorImageServiceImpl($conn);
}

function GETNomenclatorKeyService() : ?NomenclatorKeyService
{
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new NomenclatorKeyServiceImpl($conn);
}

function POSTNomenclatorKeyService() : ?NomenclatorKeyService
{
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new NomenclatorKeyServiceImpl($conn);
}



function GETSystemUserService() : ?SystemUserService
{
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new SystemUserServiceImpl($conn);
}

function POSTSystemUserService() : ?SystemUserService
{
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new SystemUserServiceImpl($conn);
}

function GETNomenclatorPlaceService(): ?NomenclatorPlaceService
{ 
    $conn = GETConnection();
    if($conn === null)
        return null;
    return new NomenclatorPlaceServiceImpl($conn);
}

function POSTNomenclatorPlaceService(): ?NomenclatorPlaceService
{ 
    $conn = POSTConnection();
    if($conn === null)
        return null;
    return new NomenclatorPlaceServiceImpl($conn);
}

