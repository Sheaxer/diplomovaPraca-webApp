<?php
require_once(__DIR__ ."/helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ ."/../entities/DigitalizedTranscription.php");
require_once (__DIR__. "/../services/DigitalizedTranscriptionService.php");
require_once (__DIR__ ."/../entities/EncryptionPair.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
function digitalizedTranscriptionController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {
        $userInfo = authorize();
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                if ((sizeof($pathElements) === 2) || (sizeof($pathElements) == 3)) {
                    if (is_numeric($pathElements[1])) {
                        $transcriptionId = intval($pathElements[1]);
                        $transcriptionService = GETDigitalizedTranscriptionService();

                        if (sizeof($pathElements) === 2) {
                            $data = $transcriptionService->getDigitalizedTranscriptionById($userInfo, $transcriptionId);
                            post_result($data);
                        } else {
                            if (strcmp($pathElements[2], "encryptionKey") === 0) {
                                $data = $transcriptionService->getEncryptionKeyByTranscriptionId($userInfo, $transcriptionId);
                                post_result($data);
                            } else if (strcmp($pathElements[2], "decryptionKey") === 0) {
                                $data = $transcriptionService->getDecryptionKeyByTranscriptionId($userInfo, $transcriptionId);
                                post_result($data);
                            } else if (strcmp($pathElements[2], "cipherCreator") === 0) {
                                {
                                    $data= digitalizedTranscriptionToCipherCreator($userInfo, $transcriptionId);
                                    post_result($data);
                                }
                            } else throw new RuntimeException("Incorrect URL");
                        }
                    } else
                        throw new RuntimeException("Incorrect Transcription ID");
                } else if(sizeof($pathElements) === 1)
                {
                    $transcriptionService = GETDigitalizedTranscriptionService();
                    $data = $transcriptionService->getAllTranscriptions($userInfo);
                    post_result($data);
                }

                break;
            default:
                throw  new RuntimeException("Only GET method allowed");
        }
    } catch (Exception $exception) {
        throwException($exception);
    }
}

function digitalizedTranscriptionToCipherCreator(?array $userInfo, int $transcriptionId) : ?array
{
    require_once (__DIR__ . "/../services/NomenclatorKeyService.php");
    require_once (__DIR__ . "/../entities/NomenclatorKey.php");

    $transcriptionService = GETDigitalizedTranscriptionService();
    $transcription = $transcriptionService->getDigitalizedTranscriptionById($userInfo, $transcriptionId);
    if($transcription === null)
        return null;

    $nomenclatorKeyService = GETNomenclatorKeyService();
    $nomenclatorKey = $nomenclatorKeyService->getNomenclatorKeyById($userInfo, $transcription->nomenclatorKeyId);

    $data = array();

    $data['signature'] = $nomenclatorKey->signature;

    if($nomenclatorKey->language !== null)
        $data['alphabet'] = $nomenclatorKey->language;
    else
        $data['alphabet'] = "ENG";

    $data['substitution'] = array();
    $data['bigrams'] = array();
    $data['trigrams'] = array();
    $data['codewords'] = array();
    $data['nulls'] = array();
    $data['order'] = array();
    $data['specialChars'] = array();
    if($nomenclatorKey->completeStructure !== null)
        $strucResult = preg_split('/[\n\r\s]+/',$nomenclatorKey->completeStructure);
    else $strucResult = false;

    $isSubstitution = false;
    $isBigram = false;
    $isTrigram = false;
    $isNulls = false;

    if($strucResult !== false)
    {
        foreach ($strucResult as $item)
        {
            if(strpos($item, "0") !== false)
                $isNulls = true;
            if(strpos($item,"1") !== false)
            {
                $isSubstitution = true;
            }

            if(strpos($item,"2") !== false)
            {
                $isBigram = true;
            }

            if(strpos($item,"3") !== false)
            {
                $isTrigram = true;
            }
        }
    }
    $encKey = $transcriptionService->getEncryptionKeyByTranscriptionId($userInfo, $transcriptionId);
    foreach ($encKey as $p => $c)
    {
       if(strlen($p) === 0)
       {
           if($isNulls)
           {
               $data['nulls'] = array_merge($data['nulls'], $c);
           }
           else
           {
               $data['codewords'] = array_merge($data['codewords'], $c);
           }
       }
       else if(strlen($p) === 1)
       {
           if($isSubstitution)
           {
                $data['substitution'][$p] = $c;
           }
           else
           {
               $data['codewords'][$p] = $c;
               /*if(sizeof($c) === 1)
                   $data['codewords'][$p] = $c[0];
               else
                   $data['codewords'][$p] = $c;*/
           }
       }
       else if(strlen($p) === 2)
       {
           if($isBigram)
           {
               $data['bigrams'][$p] = $c;
               /*if(sizeof($c) === 1)
               {
                   $data['bigrams'][$p] = $c[0];
               }
               else
                   $data['bigrams'][$p] = $c;*/
           }
           else
           {
               $data['codewords'][$p] = $c;
               /*if(sizeof($c) === 1)
               {
                   $data['codewords'][$p] = $c[0];
               }
               else
                   $data['codewords'][$p] = $c;*/
           }
       }
       else if(strlen($p) === 3)
       {
           if($isTrigram)
           {
               $data['trigrams'][$p]= $c;
               /*if(sizeof($c) === 1)
               {
                   $data['trigrams'][$p] = $c[0];
               }
               else
                   $data['trigrams'][$p]= $c;*/
           }
           else
           {
               $data['codewords'][$p] = $c;
               /*if(sizeof($c) === 1)
               {
                   $data['codewords'][$p] = $c[0];
               }
               else
                   $data['codewords'][$p] = $c;*/
           }
       }
       else
       {
           $data['codewords'][$p] = $c;
           /*if(sizeof($c) === 1)
           {
               $data['codewords'][$p] = $c[0];
           }
           else
               $data['codewords'][$p] = $c;*/
       }
    }


    $ordIndex = strpos($transcription->note,"order=");
    if($ordIndex !== false)
    {
        $ordEndIndex = strpos( substr($transcription->note,$ordIndex+6),";");
        if($ordEndIndex === false)
            $ordEndIndex = strlen(substr($transcription->note,$ordIndex+6));
        $ordSubstr = substr($transcription->note,$ordIndex+6,$ordEndIndex);
        $ord = explode(",",$ordSubstr);
        $data['order'] = $ord;
    }

    $specialCharsIndex = strpos($transcription->note,"specialChars=");
    if($specialCharsIndex !== false)
    {
        $specialCharsEndIndex = strpos(substr($transcription->note,$specialCharsIndex+13),";");
        if($specialCharsEndIndex === false)
            $specialCharsEndIndex = strlen(substr($transcription->note,$specialCharsIndex+13));
        $specialCharsSubstr = substr($transcription->note,$specialCharsIndex+13,$specialCharsEndIndex);
        $isSymbol = false;
        $specialCharSymbol = "";
        for($i = 0; $i< strlen($specialCharsSubstr); $i++)
        {

            if ($specialCharsSubstr[$i] === "\"")
            {
                if($isSymbol)
                {
                    $isSymbol = false;
                    array_push($data['specialChars'],$specialCharSymbol);
                    $specialCharSymbol = "";
                }
                else
                {
                    $isSymbol = true;
                    $specialCharSymbol = "";
                }
            }
            else
            {
                $specialCharSymbol .= $specialCharsSubstr[$i];
            }
        }
    }

    return $data;
}