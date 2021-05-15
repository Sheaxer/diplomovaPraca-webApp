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
            //var_dump($_SERVER['CONTENT_TYPE']);
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
                        $isSubstitutionLetters = $substitutionData['letters'];
                        $isSubstitutionNumbers = $substitutionData['numbers'];
                        $isSubstitutionSymbols = $substitutionData['symbols'];
                        $isSubstitutionDouble = $substitutionData['double'];
                    }

                }

                $isBigram = false;

                if(array_key_exists("bigrams",$object))
                {
                    if(sizeof($object['bigrams']) > 0)
                    {
                        $isBigram = true;

                        $bigramData = parseCipherCreatorType($object['bigrams']);
                        $isBigramLetters = $bigramData['letters'];
                        $isBigramNumbers = $bigramData['numbers'];
                        $isBigramSymbols = $bigramData['symbols'];
                        $isBigramDouble = $bigramData['double'];
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
                        $isTrigramLetters = $trigramData['letters'];
                        $isTrigramNumbers = $trigramData['numbers'];
                        $isTrigramSymbols = $trigramData['symbols'];
                        $isTrigramDouble = $trigramData['double'];
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
                        $isCodeWordsLetters = $codeWordsData['letters'];
                        $isCodeWordsNumbers = $codeWordsData['numbers'];
                        $isCodeWordsSymbols = $codeWordsData['symbols'];
                        $isCodeWordsDouble = $codeWordsData['double'];
                       $transcription->encryptionPairs = array_merge($transcription->encryptionPairs,$codeWordsData['data']);

                    }
                }

                $isNulls = false;

                if(array_key_exists("nulls",$object))
                {
                    if(sizeof($object['nulls']) > 0)
                    {
                        $isNulls = true;
                        $isNullsNumbers = false;
                        $isNullsLetters= false;
                        $isNullsSymbols= false;
                        $isNullsDouble = false;
                        foreach ($object['nulls'] as $nullText)
                        {
                            $pair = new EncryptionPair();
                            $pair->plainTextUnit = "";
                            $pair->cipherTextUnit = $nullText;

                            if(is_numeric($nullText))
                                $isNullsNumbers = true;
                            else if (strlen($nullText) != strlen(utf8_decode($nullText)))
                            {
                                $isNullsSymbols = true;
                            }
                            else if (ctype_alpha($nullText))
                            {
                                if((strlen($nullText) == 2) && ($nullText[0] == $nullText[1]))
                                    $isNullsDouble = true;
                                else
                                    $isNullsLetters = true;
                            }
                            else
                                $isNullsSymbols = true;

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
                    $a = createCipherTextStructure($isSubstitutionNumbers, $isSubstitutionLetters, $isSubstitutionSymbols, $isSubstitutionDouble);
                    switch($substitutionData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .= "|1f" . $a ."↓*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .= "|1p" . $a ."↓*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .= "|1" . $a ."↓|\n";
                    }
                }
                if($isBigram)
                {
                    $a = createCipherTextStructure($isBigramNumbers, $isBigramLetters, $isBigramSymbols, $isBigramDouble);
                    switch ($bigramData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .="|2f" . $a ."↓*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .="|2" . $a ."p↓*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .="|2" . $a ."↓|\n";
                    }
                    $nomenclatorKey->completeStructure .="|2" . $a ."↓|\n";
                }

                if($isCodeWords)
                {
                    $a = createCipherTextStructure($isCodeWordsNumbers, $isCodeWordsLetters, $isCodeWordsSymbols, $isCodeWordsDouble);
                    switch($codeWordsData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .= "|Vf" . $a ."→*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .= "|Vp" . $a ."→*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .= "|V" . $a ."→|\n";
                    }

                }
                if($isNulls)
                {
                    $a = createCipherTextStructure($isNullsNumbers, $isNullsLetters, $isTrigramSymbols, $isNullsDouble);
                    $nomenclatorKey->completeStructure .= "0" . $a ."\n";
                }

                if($isTrigram)
                {
                    $a = createCipherTextStructure($isTrigramNumbers, $isTrigramLetters, $isTrigramSymbols, $isTrigramDouble);
                    switch($trigramData['homophonic'])
                    {
                        case "full":
                            $nomenclatorKey->completeStructure .= "|3f" . $a . "↓*|\n";
                            break;
                        case "partial":
                            $nomenclatorKey->completeStructure .= "|3p" .$a . "↓*|\n";
                            break;
                        default:
                            $nomenclatorKey->completeStructure .= "|3".$a . "↓|\n";
                    }
                }
                $nomenclatorKey->completeStructure = substr($nomenclatorKey->completeStructure,0,-1);
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
                foreach ($transcription->encryptionPairs as $pair)
                {
                    $newStr = checkForLongUnicode($pair->cipherTextUnit);
                    $pair->cipherTextUnit = $newStr;

                }
                //var_dump($transcription);
                /*foreach ($transcription->encryptionPairs as $pair)
                {
                    var_dump(mb_ord($pair['cipherTextUnit']));
                }
                var_dump($transcription->encryptionPairs);*/
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

function createCipherTextStructure($isNumbers, $isLetters, $isSymbols, $isDouble)
{
    $s = "";
    if($isNumbers)
        $s .="n";
    if($isLetters)
        $s .="l";
    if($isSymbols)
        $s .="s";
    if($isDouble)
        $s .="d";
    return $s;
}

function parseCipherCreatorType(array $arr): array
{
    $isHomophonic = false;
    $isFullHomophonic = true;
    $retArray = array();
    $isNumbers= false;
    $isLetters= false;
    $isDouble = false;
    $isSymbols = false;

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
                if(is_numeric($cipherText))
                    $isNumbers = true;
                else if (strlen($cipherText) != strlen(utf8_decode($cipherText)))
                {
                    $isSymbols = true;
                }
                else if (ctype_alpha($cipherText))
                {
                    if((strlen($cipherText) == 2) && ($cipherText[0] == $cipherText[1]))
                        $isDouble = true;
                    else
                        $isLetters = true;
                }
                else
                    $isSymbols = true;
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
    $a['letters'] = $isLetters;
    $a['symbols'] = $isSymbols;
    $a['numbers'] = $isNumbers;
    $a['double'] = $isDouble;
    $a['data'] = $retArray;
    return $a;
}
