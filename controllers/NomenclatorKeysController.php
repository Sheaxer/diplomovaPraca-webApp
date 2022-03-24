<?php

require_once (__DIR__ . "/../services/NomenclatorKeyService.php");
require_once(__DIR__ ."/helpers.php");
require_once (__DIR__ . "/../config/serviceConfig.php");
require_once (__DIR__ ."/../entities/NomenclatorKey.php");
require_once (__DIR__ ."/../services/NomenclatorFolderService.php");
require_once (__DIR__ ."/../entities/NomenclatorImage.php");
require_once (__DIR__ ."/../entities/NomenclatorFolder.php");
require_once (__DIR__ ."/../entities/DigitalizedTranscription.php");
require_once (__DIR__ . "/../services/DigitalizedTranscriptionService.php");
require_once (__DIR__ . "/../entities/EncryptionPair.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
require_once (__DIR__ . "/../entities/Place.php");

function nomenclatorKeyController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {

        $userInfo = authorize();

        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                if (strcmp(substr($pathElements[0], 0, 15), "nomenclatorKeys") === 0) {
                    $nomenclatorKeyService = GETNomenclatorKeyService();
                    if (sizeof($pathElements) === 1) {
                        $pathParams = array();
                        if(strlen($pathElements[0]) > 16)
                        {
                            if($pathElements[0][15]== '?')
                                $pathParams = explode("&", substr($pathElements[0], 16));
                        }

                        //var_dump($pathParams);
                        $folders = array();
                        $structures = array();
                        $signatures = array();
                        $limit = null;
                        $page = 1;
                       // xdebug_break();
                        //var_dump($_GET['folder']);
                        foreach ($pathParams as $param) {
                            if (substr_compare($param, "folder=", 0,7) === 0)
                                array_push($folders, urldecode(substr($param, 7)));
                            else if (substr_compare($param, "completeStructure=", 0,18) === 0)
                                array_push($structures, urldecode(substr($param, 18)));
                            else if (substr_compare($param, "signature=", 0,10) === 0)
                                array_push($signatures , urldecode(substr($param, 10)));
                            else if (substr_compare($param, "limit=", 0, 6) === 0) {
                                $limit = intval(substr($param, 6));
                            }
                            else if (substr_compare($param, "page=", 0, 5) === 0) {
                                $page = intval(substr($param, 5));
                            }
                            
                        }
                        //var_dump($folders);
                        //var_dump($structures);
                        //var_dump($signatures);
                        if (empty($folders))
                            $folders = null;
                        if (empty($structures))
                            $structures = null;

                        $keys = $nomenclatorKeyService->getNomenklatorKeysByAttributes($userInfo, $limit, $page, $folders, $structures );
                        post_result($keys);

                    } else if (sizeof($pathElements) === 2 || sizeof($pathElements) === 3) {
                        if (is_numeric($pathElements[1])) {
                            $keyId = intval($pathElements[1]);

                            if (sizeof($pathElements) === 2) {
                                $key = $nomenclatorKeyService->getNomenclatorKeyById($userInfo, intval($pathElements[1]));
                                post_result($key);
                            } else {
                                if (strcmp($pathElements[2], "digitalizedTranscriptions") === 0) {
                                    $digitalizedTranscriptionService = GETDigitalizedTranscriptionService();
                                    if ($digitalizedTranscriptionService === null) {
                                        throw new Exception("System Error");
                                    }
                                    /// print all digitalized Transcriptions
                                    $data = $digitalizedTranscriptionService->getDigitalizedTranscriptionsOfNomenclator($userInfo, $keyId);
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

                if ( strcmp(substr($pathElements[0], 0, 15), "nomenclatorKeys") === 0) {

                    $object = getData();
                    if($object === null)
                        throw new Exception("No data");

                    $userInfo = authorize();
                    if (! $userInfo) {
                        throw new AuthorizationException("Not authorized");
                    }

                    if (sizeof($pathElements) == 3) {
                        if (is_numeric($pathElements[1])) {
                            if (strcmp($pathElements[2], "state") == 0) {
                                /* TODO update state */
                                if (! $userInfo || ! $userInfo['isAdmin']) {
                                    throw new AuthorizationException('You must be admin to edit nomenclator key state');
                                }

                                $note = $object['note'] ?? null;
                                $state = $object['state'] ?? null;
                                //xdebug_break();
                                $nomenclatorKeyService = POSTNomenclatorKeyService();
                                $retVal = $nomenclatorKeyService->updateNomenclatorKeyState($userInfo, $state, $note, intval($pathElements[1]), null);
                                if ($retVal) {
                                    post_result([
                                        'status' => 'success'
                                    ]);
                                } else {
                                    post_result([
                                        'status' => 'error'
                                    ]);
                                }
                            }
                        }
                    } 

                    if (sizeof($pathElements) === 1 || sizeof($pathElements) === 2) {
                        $nomenclatorKeyId = null;
                        if (sizeof($pathElements) == 2) {
                            if (is_numeric($pathElements[1])) {
                                if (! $userInfo['isAdmin']) {
                                    throw new AuthorizationException('Only admin can edit the nomenclator key');
                                }
                                $nomenclatorKeyId = intval($pathElements[1]);
                            } else throw new Exception('Invalid id');

                        }
                        //if ()
                        //xdebug_break();
                        $nomenclatorKey = new NomenclatorKey();

                        if (array_key_exists('usedChars', $object)) {
                            $nomenclatorKey->usedChars = $object['usedChars'];
                        }

                        if (array_key_exists('cipherType', $object)) {
                            $nomenclatorKey->cipherType = $object['cipherType'];
                        }

                        if (array_key_exists('keyType', $object)) {
                            $nomenclatorKey->keyType = $object['keyType'];
                        }

                        if (array_key_exists('usedFrom', $object)) {
                           
                            $nomenclatorKey->usedFrom = new DateTime($object['usedFrom']);
                            
                            
                        }

                        if (array_key_exists('usedTo', $object)) {
                            $nomenclatorKey->usedTo = new DateTime($object['usedTo']);
                        }

                        if (array_key_exists('usedAround', $object)) {
                            $nomenclatorKey->usedAround = new DateTime($object['usedAround']);
                        }

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
                            $nomenclatorKey->signature = generateRandomString(6);

                        if (array_key_exists("completeStructure", $object))
                            $nomenclatorKey->completeStructure = $object["completeStructure"];
                        else $nomenclatorKey->completeStructure = null;

                        if(array_key_exists("language",$object))
                            $nomenclatorKey->language = $object['language'];
                        else
                            $nomenclatorKey->language = null;

                        if (array_key_exists('groupId', $object)) {
                            $nomenclatorKey->groupId = $object['groupId'];
                        }

                        if (array_key_exists("nomenclatorImages", $object)) {
                            $nomenclatorKey->images = array();
                            $uploadedUrl = [];
                            $encodedUrl = [];
                            $i = 0;
                            foreach ($object['nomenclatorImages'] as $image) {
                                $nomenclatorImage = new NomenclatorImage();
                                if (!array_key_exists('url', $image)) {

                                    if (array_key_exists('string', $image)) {
                                        $decoded = base64_decode($image['string']);
                                        $name = $image['name'];

                                        $u = storeImage($decoded, $name);
                                        if ($u) {
                                            $nomenclatorImage->url = $u;
                                            $nomenclatorImage->isLocal = true;
                                            $encodedUrl[]= $u;
                                        } else {
                                            deleteFiles($uploadedUrl);
                                            deleteFiles($encodedUrl);
                                            throw new RuntimeException("Unable to store image possibly due to mime type. Allowed types are application/pdf, image/png and image/jpeg");
                                        }

                                    } else {
                                        if (empty($uploadedUrl)) {
                                            $uploadedUrl = uploadImages();
                                        }
                                        if ($i >= sizeof($uploadedUrl)) {
                                            deleteFiles($uploadedUrl);
                                            deleteFiles($encodedUrl);
                                            throw new RuntimeException("Incorrect number of images uploaded");
                                        }
                                        $nomenclatorImage->url = $uploadedUrl[$i];
                                        $nomenclatorImage->isLocal = true;
                                        $i++;
                                    }

                                    

                                } else {
                                    $nomenclatorImage->url = $image['url'];
                                    $nomenclatorImage->isLocal = false;
                                }

                                if (array_key_exists("structure", $image))
                                    $nomenclatorImage->structure = $image['structure'];

                                if (array_key_exists('hasInstructions', $image)) {
                                    $nomenclatorImage->hasInstructions = $image['hasInstructions'];
                                } else {
                                    $nomenclatorImage->hasInstructions = false;
                                }

                                array_push($nomenclatorKey->images, $nomenclatorImage);
                            }
                            if (($uploadedUrl !== null) && ($i !== sizeof($uploadedUrl))) {
                                deleteFiles($uploadedUrl);
                                throw new RuntimeException("Incorrect number of images uploaded");
                            }
                        }
                        else
                            $nomenclatorKey->images = null;
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
                                if (array_key_exists('isMainUser', $keyUser)) {
                                    $k->isMainUser = $keyUser['isMainUser'];
                                    
                                } else {
                                    $k->isMainUser = false;
                                }
                                array_push($nomenclatorKey->keyUsers, $k);
                            }
                        }
                        else {
                            $nomenclatorKey->keyUsers = null;
                        }

                        if (array_key_exists('placeOfCreationId', $object)) {
                            $nomenclatorKey->placeOfCreationId = $object['placeOfCreationId'];
                        } else {
                            $nomenclatorKey->placeOfCreationId = null;
                        }
                           
                        $nomenclatorKeyService = POSTNomenclatorKeyService();

                       

                        if ($nomenclatorKeyService === null)
                            throw new Exception("System Error");
                        
                            /*if ($nomenclatorKeyId) {
                                $nomenclatorKey->id = $nomenclatorKeyId;
                                $nomenclatorKeyService->updateNomenclatorKeyState();
                            }*/
                        $addedId = $nomenclatorKeyService->createNomenclatorKey($userInfo['id'], $nomenclatorKey);
                        if (isset ($addedId['exception'])) {
                            throw new Exception($addedId['exception']);
                        }
                        post_result($addedId);
                        // post to nomenclator
                    } else if (sizeof($pathElements) === 3) {

                        if (is_numeric($pathElements[1])) {

                            $keyId = intval($pathElements[1]);
                            if (strcmp($pathElements[2], "digitalizedTranscriptions") === 0) {


                                $nomenclatorKeyService = GETNomenclatorKeyService();
                                if ($nomenclatorKeyService->nomenclatorKeyExistsById($userInfo ,$keyId) === false)
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

                                    $newStr = checkForLongUnicode($pair['cipherTextUnit']);
                                    $encPair->cipherTextUnit = $newStr;

                                    $encPair->plainTextUnit = $pair['plainTextUnit'];
                                    array_push($transcription->encryptionPairs, $encPair);
                                }

                                $data['id'] = $digitalizedTranscriptionService->createDigitalizedTranscription($transcription, $keyId, $userInfo['id']);
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
    $urls = array();
    if (!isset($_FILES['nomenclatorImage']['error'])) {
        return $urls;
    }
    if(is_array($_FILES['nomenclatorImage']['error'])) {
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
            $j = 0;
            $fileName =  pathinfo($_FILES['nomenclatorImage']['name'][$i])['filename'];

            while (file_exists(sprintf(IMAGEUPLOADPATH . '%s.%s',
                $fileName,
                $ext[$i])))
            {
                $fileName = pathinfo($_FILES['nomenclatorImage']['name'][$i])['filename'] . "_" . strval($j);
                $j++;
            }

            if(!move_uploaded_file(
                $tmpName,
                sprintf(IMAGEUPLOADPATH . '%s.%s',
                    $fileName,
                    $ext[$i])
            ))
            {
                deleteFiles($urls);
                throw new Exception('Failed to move uploaded file.');
            }
            else
            {
                array_push($urls, sprintf(SERVICEPATH .IMAGEUPLOADPATH . '%s.%s',
                    $fileName,
                    $ext[$i]));

                $i++;
            }
        }
    }
    else
    {
        $fileName = pathinfo($_FILES['nomenclatorImage']['name'])['filename'];
        $j = 0;

        while(file_exists(sprintf( IMAGEUPLOADPATH . '%s.%s',
            $fileName,
            $ext)))
        {
            $fileName = pathinfo($_FILES['nomenclatorImage']['name'])['filename'] . "_" . strval($j);
            $j++;
        }

        if (!move_uploaded_file(
            $_FILES['nomenclatorImage']['tmp_name'],
            sprintf( IMAGEUPLOADPATH . '%s.%s',
                $fileName,
                $ext
            )
        )) {
            throw new Exception('Failed to move uploaded file.');
        }
        else
        {
            array_push($urls, sprintf( SERVICEPATH . IMAGEUPLOADPATH . '%s.%s',
               $fileName,
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
        $u = substr($url,strlen(SERVICEPATH));
        unlink(__DIR__ . "/../" .$u);
    }
}

function storeImage($string, $name): ?string
{
    if (strpos($name, '.') !== false) {
        $name = substr($name, 0, strpos($name, '.'));
    }

    $f = finfo_open();
    $mimeType = finfo_buffer($f, $string, FILEINFO_MIME_TYPE);

    $ext = '';
    switch ($mimeType) {
        case 'application/pdf': 
            $ext = 'pdf';
            break;
        case 'image/png': 
            $ext = 'png';
            break;
        case 'image/jpeg':
            $ext=  'jpg';
            break;
        default:
            return false;
    }
    $i = 0;
    $fileName = $name;
    while (file_exists( IMAGEUPLOADPATH . $fileName . '.' . $ext )) {
        $fileName = $name . strval($i);
        $i++;  
    }
    file_put_contents(IMAGEUPLOADPATH . $fileName . '.' . $ext, $string);
    $url = (SERVICEPATH . IMAGEUPLOADPATH . $fileName . '.' . $ext);
    return $url;
}