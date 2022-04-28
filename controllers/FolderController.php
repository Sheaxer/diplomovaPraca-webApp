<?php

require_once (__DIR__ ."/helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ . "/../services/NomenclatorFolderService.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
require_once (__DIR__ . "/../entities/NomenclatorFolder.php");
require_once (__DIR__ . "/../entities/Archive.php");
require_once (__DIR__ . "/../entities/Fond.php");
require_once (__DIR__ . "/../entities/NomenclatorFolder.php");
require_once (__DIR__ . "/../entities/Region.php");

function folderController()
{
    $pathElements = getPathElements();
    $pathParams = [];
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                xdebug_break();
                $page = 1;
                $limit = null;
                if (strlen($pathElements[0]) > 8 ) {
                    if ($pathElements[0][7] == '?') {
                        $pathParams = explode("&", substr($pathElements[0], 8));
                    }
                }
                foreach ($pathParams as $pathParam) {
                    if (substr_compare($pathParam, "page=", 0,5) === 0) {
                        $page = intval(substr($pathParam, 5));
                    }
                    else if (substr_compare($pathParam, "limit=", 0, 6) === 0) {
                        $limit = intval(substr($pathParam, 6));
                    }
                }
                $folderService = GETNomenclatorFolderService();
                $folders = $folderService->getAllFolders($limit, $page);
                post_result($folders);
                break;
            case 'POST':
                authorize();
                //xdebug_break();
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
                    $folder->regions = [];
                    foreach ($object['regions'] as $regionObject ) {
                        $region = new Region();
                        if (isset($regionObject['id'])) {
                            $region->id = $regionObject['id'];
                        }
                        if (isset($regionObject['description'])) {
                            $region->description = $regionObject['description'];
                        }
                        if (! $region->id && ! $region->description) {
                            throw new Exception("Missing required params");
                        }
                        $folder->regions[] = $region;
                    } 
                }
                if (isset ($object['fond'])) {
                    $fondObject = $object['fond'];
                    $fond = new Fond();
                    if (isset($fondObject['name'])) {
                        $fond->name = $fondObject['name'];
                    }
                    if (isset ($fondObject['archive'])) {
                        $archiveObject = $fondObject['archive'];
                        $archive = new Archive();
                        if (isset($archiveObject['country'])) {
                            $archive->country = $archiveObject['country'];
                        }
                        if (isset($archiveObject['name'])) {
                            $archive->name = $archiveObject['name'];
                        }
                        if (isset($archiveObject['shortName'])) {
                            $archive->shortName = $archiveObject['shortName'];
                        }
                        $fond->archive = $archive;
                    }
                    $folder->fond = $fond;
                }
                $folderService = POSTNomenclatorFolderService();
                if ($folderService->folderExists($folder->name)) {
                    throw new Exception("Folder already exists");
                }
                $retData = $folderService->createFolder($folder);
                if ($retData['status'] == 'success') {
                    post_result($retData);
                } else {
                    throw new Exception($retData['error']);
                }

                break;
            default:
                throw new RuntimeException("Only GET / POST method allowed for this endpoint");
        }
    }
    catch (Exception $exception)
    {
        throwException($exception);
    }
}

