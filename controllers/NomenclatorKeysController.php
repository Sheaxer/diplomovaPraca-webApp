<?php

require_once ("services/NomenclatorKeyService.php");
require_once("helpers.php");
require_once ("config/serviceConfig.php");
require_once ("entities/NomenclatorKey.php");
require_once ("services/NomenclatorFolderService.php");
require_once ("entities/NomenclatorImage.php");
require_once ("entities/NomenclatorFolder.php");
require_once ("entities/DigitalizedTranscription.php");
require_once ("services/DigitalizedTranscriptionService.php");
require_once ("entities/EncryptionPair.php");
function nomenclatorKeyController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {


        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                if (strcmp(substr($pathElements[0], 0, 15), "nomenclatorKeys") === 0) {
                    $nomenclatorKeyService = GETNomenclatorKeyService();
                    if (sizeof($pathElements) === 1) {
                        $pathParams = explode("&", substr($pathElements[0], 15));
                        $folders = array();
                        $structures = array();
                        $signatures = array();
                        //var_dump($_GET['folder']);
                        foreach ($pathParams as $param) {
                            if (substr_compare($param, "folder=", 0) === 0)
                                array_push($folders, urldecode(substr($param, 7)));
                            else if (substr_compare($param, "structure=", 0) === 0)
                                array_push($structures, urldecode(substr($param, 10)));
                            else if (substr_compare($param, "signature=", 0) === 0)
                                array_push($signatures , urldecode(substr($param, 10)));
                        }


                        if (empty($folders))
                            $folders = null;
                        if (empty($structures))
                            $structures = null;

                        $keys = $nomenclatorKeyService->getNomenklatorKeysByAttributes($folders, $structures);
                        post_result($keys);

                    } else if (sizeof($pathElements) === 2 || sizeof($pathElements) === 3) {
                        if (is_numeric($pathElements[1])) {
                            $keyId = intval($pathElements[1]);

                            if (sizeof($pathElements) === 2) {
                                $key = $nomenclatorKeyService->getNomenclatorKeyById(intval($pathElements[1]));
                                post_result($key);
                            } else {
                                if (strcmp($pathElements[2], "digitalizedTranscriptions")) {
                                    $digitalizedTranscriptionService = GETDigitalizedTranscriptionService();
                                    if ($digitalizedTranscriptionService === null) {
                                        throw new Exception("System Error");
                                    }
                                    /// print all digitalized Transcriptions
                                    $data = $digitalizedTranscriptionService->getDigitalizedTranscriptionsOfNomenclator($keyId);
                                    post_result($data);
                                }
                            }
                        }
                    } else
                        throw  new Exception("Incorrect URL");
                } else
                    throw new Exception("Incorrect URL");
                break;
            case "POST":
                if (strcmp($pathElements[0], "nomenclatorKeys") === 0) {

                    $object = getData();
                    if($object === null)
                        throw new Exception("No data");

                    $userId = authorize();

                    if (sizeof($pathElements) === 1) {
                        $nomenclatorKey = new NomenclatorKey();
                        if (array_key_exists("folder", $object)) {
                            $folderService = GETNomenclatorFolderService();
                            if ($folderService === null)
                                throw new Exception("System Error");
                            $folderExists = $folderService->folderExists($object["folder"]);
                            if ($folderExists === false)
                                throw new Exception("Incorrect Folder");
                            else
                                $nomenclatorKey->folder = $object['folder'];

                        } else $nomenclatorKey->folder = null;

                        if (array_key_exists("signature", $object)) {
                            $nomenclatorKey->signature = $object["signature"];
                        } else
                            throw new Exception("Signature is missing");

                        if (array_key_exists("completeStructure", $object))
                            $nomenclatorKey->completeStructure = $object["completeStructure"];

                        if (array_key_exists("nomenclatorImages", $object)) {
                            $nomenclatorKey->images = array();
                            $uploadedUrl = null;
                            $i = 0;
                            foreach ($object['nomenclatorImages'] as $image) {
                                $nomenclatorImage = new NomenclatorImage();
                                if (!array_key_exists('url', $image)) {
                                    if ($uploadedUrl === null) {
                                        $uploadedUrl = uploadImages();
                                    }
                                    if ($i >= sizeof($uploadedUrl)) {
                                        deleteFiles($uploadedUrl);
                                        throw new RuntimeException("Incorrect number of images uploaded");
                                    }
                                    $nomenclatorImage->url = $uploadedUrl[$i];
                                    $nomenclatorImage->isLocal = true;
                                    $i++;

                                } else {
                                    $nomenclatorImage->url = $image['url'];
                                    $nomenclatorImage->isLocal = false;
                                }

                                if (array_key_exists("structure", $image))
                                    $nomenclatorImage->structure = $image['structure'];

                                array_push($nomenclatorKey->images, $nomenclatorImage);
                            }
                            if (($uploadedUrl !== null) && ($i !== sizeof($uploadedUrl))) {
                                deleteFiles($uploadedUrl);
                                throw new RuntimeException("Incorrect number of images uploaded");
                            }
                        }
                        if(array_key_exists("keyUsers",$object))
                        {
                            $nomenclatorKey->keyUsers = array();
                            foreach ($object['keyUsers'] as $keyUser)
                            {
                                $k = new KeyUser();
                                if(array_key_exists("id",$keyUser))
                                {
                                    $k->id = $keyUser['id'];
                                }
                                if(array_key_exists("name",$keyUser))
                                {
                                    $k->name = $keyUser['name'];
                                }
                                array_push($nomenclatorKey->keyUsers,$k);
                            }
                        }
                        else
                            $nomenclatorKey->keyUsers = null;
                        $nomenclatorKeyService = POSTNomenclatorKeyService();
                        if ($nomenclatorKeyService === null)
                            throw new Exception("System Error");
                        $addedId['id'] = $nomenclatorKeyService->createNomenclatorKey($userId, $nomenclatorKey);
                        post_result($addedId);
                        // post to nomenclator
                    } else if (sizeof($pathElements) === 3) {
                        if (is_numeric($pathElements[1])) {
                            $keyId = intval($pathElements[1]);
                            if (strcmp($pathElements[2], "digitalizedTranscriptions")) {
                                $nomenclatorKeyService = GETNomenclatorKeyService();
                                if ($nomenclatorKeyService->nomenclatorKeyExistsById($keyId) === false)
                                    throw new RuntimeException("Nomenclator Key with supplied id does not exist");

                                $transcription = new DigitalizedTranscription();
                                $digitalizedTranscriptionService = POSTDigitalizedTranscriptionService();

                                if (array_key_exists("digitalizationVersion", $object)) {
                                    $transcription->digitalizationVersion = $object['digitalizationVersion'];
                                }
                                if (array_key_exists("note", $object)) {
                                    $transcription->note = $object['note'];
                                }

                                if (!array_key_exists("encryptionPairs", $object))
                                    throw new RuntimeException("Digitalized Transcription does not contain encryption pairs");

                                $transcription->encryptionPairs = array();
                                foreach ($object['encryptionPairs'] as $pair) {
                                    $encPair = new EncryptionPair();
                                    if (!array_key_exists("plainTextUnit", $pair))
                                        throw new RuntimeException("Incorrect Encryption Pair format");
                                    if (!array_key_exists("cipherTextUnit", $pair))
                                        throw new RuntimeException("Incorrect Encryption Pair format");

                                    $encPair->cipherTextUnit = $pair['cipherTextUnit'];
                                    $encPair->plainTextUnit = $pair['plainTextUnit'];
                                    array_push($transcription->encryptionPairs, $encPair);
                                }

                                $data['id'] = $digitalizedTranscriptionService->createDigitalizedTranscription($transcription, $keyId, $userId);
                                post_result($data);
                                // post new transcription
                            }
                        }
                    }

                }
        }
    } catch (Exception $exception) {
        throwException($exception);
    }

}

function uploadImages() :array
{
    echo "Uploading Images";
    $urls = array();
    if (!isset($_FILES['nomenclatorImage']['error'])) {
        return $urls;
    }
    if(is_array($_FILES['nomenclatorImage']['error'])) {
        echo "IT IS AN ARRAY";
        foreach ($_FILES['nomenclatorImage']['error'] as $err) {
            switch ($err) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }
        }
    }
    else
    {
        echo "IT IS NOT AN ARRAY";
        switch ($_FILES['nomenclatorImage']['error'])
        {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit.');
            default:
                throw new RuntimeException('Unknown errors.');
        }
    }
    // Check $_FILES['nomenklatorImage']['error'] value.
    if(is_array($_FILES['nomenclatorImage']['size'])) {
        foreach ($_FILES['nomenclatorImage']['size'] as $size) {
            if ($size > FILESIZELIMIT) {
                throw new RuntimeException('Exceeded filesize limit.');
            }
        }
    }
    else
    {
        if ($_FILES['nomenclatorImage']['size'] > FILESIZELIMIT) {
            throw new RuntimeException('Exceeded filesize limit.');
        }
    }
    // You should also check filesize here.


    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $i = 0;
    $ext = array();
    if(is_array($_FILES['nomenclatorImage']['tmp_name'])) {
        foreach ($_FILES['nomenclatorImage']['tmp_name'] as $tmpName) {
            array_push($ext, array_search(
                $finfo->file($tmpName),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'pdf' => 'application/pdf',
                ),
                true
            ));
            if ($ext[$i] === false) {
                throw new RuntimeException('Invalid file format.');
            }
            $i++;
        }
    }
    else {
        if (false === $ext = array_search(
                $finfo->file($_FILES['nomenclatorImage']['tmp_name']),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'pdf' => 'application/pdf',
                ),
                true
            )) {
            throw new RuntimeException('Invalid file format.');
        }
    }
    $i = 0;
    //var_dump($finfo->file($_FILES['nomenklatorImage']['tmp_name']));
    if(is_array($_FILES['nomenclatorImage']['name']))
    {
        foreach ($_FILES['nomenclatorImage']['tmp_name'] as $tmpName)
        {

            if(!move_uploaded_file(
                $tmpName,
                sprintf(IMAGEUPLOADPATH . '%s.%s',
                    pathinfo($_FILES['nomenclatorImage']['name'][$i])['filename'],
                    $ext[$i])
            ))
            {
                deleteFiles($urls);
                throw new Exception('Failed to move uploaded file.');
            }
            else
            {
                array_push($urls, sprintf(SERVICEPATH .IMAGEUPLOADPATH . '%s.%s',
                    pathinfo($_FILES['nomenclatorImage']['name'][$i])['filename'],
                    $ext[$i]));

                $i++;
            }
        }
    }
    else
    {
        if (!move_uploaded_file(
            $_FILES['nomenclatorImage']['tmp_name'],
            sprintf( IMAGEUPLOADPATH . '%s.%s',
                pathinfo($_FILES['nomenclatorImage']['name'])['filename'],
                $ext
            )
        )) {
            throw new Exception('Failed to move uploaded file.');
        }
        else
        {
            array_push($urls, sprintf( SERVICEPATH . IMAGEUPLOADPATH . '%s.%s',
                pathinfo($_FILES['nomenclatorImage']['name'])['filename'],
                $ext
            ));
        }
    }
    return $urls;
}

function deleteFiles(array $urls)
{
    if(sizeof($urls) === 0)
        return;
    foreach($urls as $url)
    {
        unlink($url);
    }
}