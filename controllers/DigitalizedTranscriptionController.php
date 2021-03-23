<?php
require_once("helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ ."/../entities/DigitalizedTranscription.php");
require_once (__DIR__. "/../services/DigitalizedTranscriptionService.php");
require_once (__DIR__ ."/../entities/EncryptionPair.php");
function digitalizedTranscriptionController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                if ((sizeof($pathElements) === 2) || (sizeof($pathElements) == 3)) {
                    if (is_numeric($pathElements[1])) {
                        $transcriptionId = intval($pathElements[1]);
                        $transcriptionService = GETDigitalizedTranscriptionService();

                        if (sizeof($pathElements) === 2) {
                            $data = $transcriptionService->getDigitalizedTranscriptionById($transcriptionId);
                            post_result($data);
                        } else {
                            if (strcmp($pathElements[2], "encryptionKey") === 0) {
                                $data = $transcriptionService->getEncryptionKeyByTranscriptionId($transcriptionId);
                                post_result($data);
                            } else if (strcmp($pathElements[2], "decryptionKey") === 0) {
                                $data = $transcriptionService->getDecryptionKeyByTranscriptionId($transcriptionId);
                                post_result($data);
                            } else if (strcmp($pathElements[2], "cipherCreator") === 0) {
                                // TODO
                            } else throw new RuntimeException("Incorrect URL");
                        }
                    } else
                        throw new RuntimeException("Incorrect Transcription ID");
                } else throw new RuntimeException("Incorrect URL");

                break;
            default:
                throw  new RuntimeException("Only GET method allowed");
        }
    } catch (Exception $exception) {
        throwException($exception);
    }
}