<?php

require_once (__DIR__ ."/helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ . "/../services/NomenclatorPlaceService.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
require_once (__DIR__ . "/../entities/Place.php");

function placeController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                $page = 1;
                $pathParams = [];
                $limit = Place::LIMIT;
                if (strlen($pathElements[0]) > 8 ) {
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
                $placeService = GETNomenclatorPlaceService();
                $places = $placeService->getAllPlaces($limit, $page);
                post_result($places);
                break;
            case "POST":
                $object = getData();
                if($object === null)
                    throw new Exception("No data");
                $userInfo = authorize();
                if (! $userInfo) {
                    throw new AuthorizationException("Not authorized");
                }
                if (! array_key_exists('name', $object)) {
                    throw new Exception("Name missing");
                }
                $placeService = POSTNomenclatorPlaceService();
                $result = $placeService->createPlace($object['name']);
                if ($result['success']) {
                    post_result([
                        'id' => $result['id'],
                    ]);
                } else {
                    throw new Exception($result['error']);
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