<?php

require_once ("helpers.php");
require_once (__DIR__ . "/../config/serviceConfig.php");
require_once (__DIR__ . "/../services/NomenclatorKeyService.php");
require_once (__DIR__ . "/NomenclatorKeysController.php");

require_once (__DIR__ . "/../entities/NomenclatorKey.php");
require_once (__DIR__ . "/../entities/DigitalizedTranscription.php");
require_once (__DIR__ . "/../services/DigitalizedTranscriptionService.php");
require_once (__DIR__ . "/../entities/EncryptionPair.php");

require_once (__DIR__ . "/../entities/NomenclatorImage.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");

function cipherCreatorController()
{
    try {


        switch ($_SERVER['REQUEST_METHOD']) {
            case "POST":
                $object = getData();
                //var_dump($object);
                if ($object === null)
                    throw new RuntimeException("No data");

                $userId = authorize(); // AUTHORIZE

                //var_dump($object);
                $nomenclatorKey = new NomenclatorKey();
                $transcription = new DigitalizedTranscription();
                $transcription->encryptionPairs = array();
                $isSubstitution = false;
                if(array_key_exists("substitution",$object))
                {

                    if(sizeof($object['substitution']) > 0)
                    {
                        $isSubstitution = true;
                        $substitutionData = parseCipherCreatorType($object['substitution']);
                        $transcription->encryptionPairs = array_merge($transcription->encryptionPairs,$substitutionData['data']);
                    }

                }

                $isBigram = false;

                if(array_key_exists("bigrams",$object))
                {
                    if(sizeof($object['bigrams']) > 0)
                    {
                        $isBigram = true;

                        $bigramData = parseCipherCreatorType($object['bigrams']);
                        $transcription->encryptionPairs = array_merge($transcription->encryptionPairs,$bigramData['data']);
                    }
                }

                $isTrigram = false;

                if(array_key_exists("trigrams",$object))
                {
                    if(sizeof($object['trigrams']) > 0)
                    {
                        $isTrigram = true;

                        $trigramData = parseCipherCreatorType($object['trigrams']);
                        $transcription->encryptionPairs = array_merge($transcription->encryptionPairs,$trigramData['data']);
                    }
                }

                $isCodeWords= false;
                if(array_key_exists("codewords",$object))
                {
                    if(sizeof($object['codewords']) > 0)
                    {
                        $isCodeWords = true;

                       $codeWordsData = parseCipherCreatorType($object['codewords']);
                       $transcription->encryptionPairs = array_merge($transcription->encryptionPairs,$codeWordsData['data']);

                    }
                }

                $isNulls = false;

                if(array_key_exists("nulls",$object))
                {
                    if(sizeof($object['nulls']) > 0)
                    {
                        $isNulls = true;
                        foreach ($object['nulls'] as $nullText)
                        {
                            $pair = new EncryptionPair();
                            $pair->plainTextUnit = "";
                            $pair->cipherTextUnit = $nullText;
                            //var_dump($pair);
                            array_push($transcription->encryptionPairs,$pair);
                        }

                    }
                }

                $nomenclatorKey->folder = null;
                $nomenclatorKey->keyUsers = null;

                if(array_key_exists("signature",$object))
                    $nomenclatorKey->signature = $object['signature'];
                else
                {
                    if(isset($_POST['signature']) && !empty($_POST['signature']) && $_POST['signature'] !== null)
                        $nomenclatorKey->signature = $_POST['signature'];
                    else
                        $nomenclatorKey->signature = null;
                }

                $nomenclatorKey->completeStructure = "";
                if($isSubstitution)
                {
                    switch($substitutionData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .= "|1fn↓*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .= "|1pn↓*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .= "|1n↓*|\n";
                    }
                }
                if($isBigram)
                {
                    switch ($bigramData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .="|2fn↓*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .="|2np↓*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .="|2n↓|\n";
                    }
                    $nomenclatorKey->completeStructure .="|2n↓|\n";
                }

                if($isCodeWords)
                {
                    switch($codeWordsData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .= "|Nfn→*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .= "|Npn→*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .= "|Nn→|\n";
                    }

                }
                if($isNulls)
                {
                    $nomenclatorKey->completeStructure .= "0\n";
                }

                if($isTrigram)
                {
                    switch($trigramData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .= "|3fn↓*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .= "|3pn↓*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .= "|3n↓|\n";
                    }
                }
                $urls = uploadImages();

                if(!empty($urls))
                {
                    $nomenclatorKey->images = array();
                    $nomenclatorImage = new NomenclatorImage();
                    $nomenclatorImage->url = $urls[0];
                    $nomenclatorImage->isLocal = true;
                    $nomenclatorImage->structure = $nomenclatorKey->completeStructure;
                    array_push($nomenclatorKey->images,$nomenclatorImage);
                }
                else
                    $nomenclatorKey->images = null;

                $transcription->digitalizationVersion = "cipher creator";
                $transcription->note = "";

                if(array_key_exists("alphabet",$object))
                {
                    $nomenclatorKey->language = $object['alphabet'];
                }
                else
                    $nomenclatorKey->language = null;
                //var_dump($transcription->note);
                $transcription->note = "";
                if(array_key_exists("order",$object))
                {
                    $transcription->note .= "order=";
                    foreach ($object['order'] as $item)
                    {
                        $transcription->note .= $item;
                        $transcription->note .= ",";
                    }
                    $transcription->note = substr($transcription->note,0,-1);
                    $transcription->note .= ";";
                }

                if(array_key_exists("specialChars",$object))
                {
                    $transcription->note .= "specialChars=";
                    foreach ($object['specialChars'] as $item)
                    {
                        $transcription->note .= '"';
                        $transcription->note .= $item;
                        $transcription->note .= '"';
                        $transcription->note .= ",";
                    }
                    $transcription->note = substr($transcription->note,0,-1);
                    $transcription->note .= ';';
                }
                //var_dump($transcription->note);
                //echo "END OF NOTE";

                //var_dump($nomenclatorKey);
                //var_dump($transcription);

                $nomenclatorKeyService = POSTNomenclatorKeyService();
                $newId = $nomenclatorKeyService->createNomenclatorKey($userId,$nomenclatorKey);
                $digitalizedTranscriptionService = POSTDigitalizedTranscriptionService();

                $digId = $digitalizedTranscriptionService->createDigitalizedTranscription($transcription,$newId,$userId);

                $retData['nomenclatorKeyId'] = $newId;
                $retData['digitalizedTranscriptionId'] = $digId;

                post_result($retData);

                break;
            default:
                throw new Exception("ONLY POST ALLOWED FOR THIS ENDPOINT");
        }
    }
    catch (Exception $exception)
    {
        throwException($exception);
    }
}

function parseCipherCreatorType(array $arr): array
{
    $isHomophonic = false;
    $isFullHomophonic = true;
    $retArray = array();

    foreach ($arr as $plainText => $cipherTextUnit)
    {
        if(is_array($cipherTextUnit))
        {
            if(sizeof($cipherTextUnit) > 1)
            {
                $isHomophonic = true;
            }
            else
            {
                if($isHomophonic)
                    $isFullHomophonic = false;
            }

            foreach ($cipherTextUnit as $cipherText)
            {
                $pair = new EncryptionPair();
                $pair->plainTextUnit = $plainText;
                $pair->cipherTextUnit = $cipherText;
                array_push($retArray,$pair);
            }

        }
        else
        {
            if($isHomophonic)
                $isFullHomophonic = false;

            $pair = new EncryptionPair();
            $pair->plainTextUnit = $plainText;
            $pair->cipherTextUnit = $cipherTextUnit;
            array_push($retArray,$pair);
        }
    }

    $a = array();
    if($isHomophonic)
    {
        if($isFullHomophonic)
        {
            $a['homophonic'] = "full";
        }
        else
        {
            $a['homophonic'] = "partial";
        }
    }
    else
    {
        $a['homophonic'] = "none";
    }
    $a['data'] = $retArray;
    return $a;
}