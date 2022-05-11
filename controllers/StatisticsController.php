<?php
require_once(__DIR__ ."/helpers.php");
require_once (__DIR__ . '/../services/StatisticsService.php');

function statisticsController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();

    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET": 
                if (sizeof($pathElements) == 1) {
                    if ($pathElements[0] == 'statistics') {
                        $statisticsService = GETStatisticsService();
                        $statistics = $statisticsService->getStatistics();
                        post_result($statistics);
                    }
                    throw new Exception("Unknown endpoint");
                }
                break;
                default: throw new Exception("Only GET method is allowed");
        }
    }catch (Exception $exception) {
        throwException($exception);
    }
}