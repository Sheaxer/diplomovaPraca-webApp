<?php

require_once (__DIR__ ."/helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ . "/../services/NomenclatorFolderService.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
function folderController()
{
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                $folderService = GETNomenclatorFolderService();
                $folders = $folderService->getAllFolders();
                post_result($folders);
                break;
            default:
                throw new RuntimeException("Only GET method allowed for this endpoint");
        }
    }
    catch (Exception $exception)
    {
        throwException($exception);
    }
}

