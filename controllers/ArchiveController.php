<?php

require_once (__DIR__ ."/helpers.php");
require_once (__DIR__ . "/../config/serviceConfig.php");

function archiveController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();

    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET": 
                if (sizeof($pathElements) == 1) {
                    if (strcmp(substr($pathElements[0], 0, 8 ), "archives") == 0) {
                        $archiveService = GETArchiveService();
                        $limit = null;
                        $page = null;
                        if (isset($_GET['limit']) && !empty($_GET['limit']) && $_GET['limit'] != null ) {
                            $limit = intval($_GET['limit']);
                        }
                        if (isset($_GET['page']) && !empty($_GET['page']) && $_GET['page'] != null ) {
                            $limit = intval($_GET['page']);
                        }

                        $result = $archiveService->getArchives($limit, $page);

                        post_result($result);
                    } else {
                        throw new Exception("unknown url");
                    }
                } else {
                    throw new Exception("unknown url");
                }
                break;
            default: 
                throw new Exception("Only GET method is supported");
        }
    } catch (Exception $exception) {
        throwException($exception);
    }
}