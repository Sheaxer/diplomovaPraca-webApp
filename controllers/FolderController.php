<?php

require_once (__DIR__ ."/helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ . "/../services/NomenclatorFolderService.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
require_once (__DIR__ . "/../entities/NomenclatorFolder.php");

function folderController()
{
    $pathElements = getPathElements();
    $pathParams = [];
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                $page = 1;
                $limit = NomenclatorFolder::LIMIT;
                if (sizeof($pathElements[0]) > 8 ) {
                    if ($pathElements[0][7] == '?') {
                        $pathParams = explode("&", substr($pathElements[0], 8));
                    }
                }
                foreach ($pathParams as $pathParam) {
                    if (substr_compare($pathParam, "page=", 0,5) === 0) {
                        $page = intval(substr($pathParam, 5));
                    }
                    else if (substr_compare($pathParam, "limit=", 0, 6)) {
                        $limit = intval(substr($pathParam, 6));
                    }
                }
                $folderService = GETNomenclatorFolderService();
                $folders = $folderService->getAllFolders($limit, $page);
                post_result($folders);
                break;
            case 'POST':
                authorize();

                $object = getData();
                if (! $object || ! isset($object['name']) || ! isset($object['fond'])) {
                    throw new Exception('Folder needs to contain name and fond');
                }

                $folder = new NomenclatorFolder();
                $folder->name = $object['name'];
                if (isset ($object['endDate'])) {
                    $folder->endDate = new DateTime($object['endDate']);
                }
                if (isset ($object['startDate'])) {
                    $folder->startDate = new DateTime($object['startDate']);
                }
                if (isset($object['regions'])) {
                    foreach ($object['regions'] as $region ) {
                        
                    } 
                }

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

