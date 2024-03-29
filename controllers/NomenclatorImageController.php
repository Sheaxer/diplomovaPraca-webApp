<?php

require_once(__DIR__ ."/helpers.php");
require_once (__DIR__ . "/../config/serviceConfig.php");
require_once (__DIR__ ."/../entities/NomenclatorImage.php");


function imageController()
{
    //xdebug_break();
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                if (strcmp(substr($pathElements[0], 0, 20), "nomenclatorKeyImages") === 0) {
                    $userInfo = authorize();
                    if (! $userInfo) {
                        throw new AuthorizationException("Not authorized");
                    }
                    if (isMultiPart()) {
                        $uploadedUrl = [];
                        $uploadedUrl = uploadImages();
                        if ($uploadedUrl && sizeof($uploadedUrl) > 0) {
                            post_result([
                                'urls' => $uploadedUrl
                            ]);
                        } else {
                            post_result(null);
                        }
                    }
                    $object = getData();

                    $nomenclatorKeyImages = array();
                   
                    $encodedUrl = [];
                    $i = 0;
                    $isSingle = false;
                    foreach ($object as $image) {
                        if (! is_array($image)) {
                            $isSingle = true;
                            break;
                        }
                        $nomenclatorImage = new NomenclatorImage();

                        if (array_key_exists('string', $image)) {
                            $decoded = base64_decode($image['string']);
                            $name = $image['name'];

                            $u = storeImage($decoded, $name);
                            if ($u) {
                                $nomenclatorImage->url = $u;
                                $nomenclatorImage->isLocal = true;
                                $encodedUrl[]= $u;
                            } else {
                                //deleteFiles($uploadedUrl);
                                deleteFiles($encodedUrl);
                                throw new RuntimeException("Unable to store image possibly due to mime type. Allowed types are application/pdf, image/png and image/jpeg");
                            }
                            array_push($nomenclatorKeyImages, $nomenclatorImage);

                        }  
                    }
                    if ($isSingle) {
                        $nomenclatorImage = new NomenclatorImage();

                        if (array_key_exists('string', $object)) {
                            $decoded = base64_decode($object['string']);
                            $name = $object['name'];

                            $u = storeImage($decoded, $name);
                            if ($u) {
                                $nomenclatorImage->url = $u;
                                $nomenclatorImage->isLocal = true;
                                $encodedUrl[]= $u;
                            } else {
                                //deleteFiles($uploadedUrl);
                                deleteFiles($encodedUrl);
                                throw new RuntimeException("Unable to store image possibly due to mime type. Allowed types are application/pdf, image/png and image/jpeg");
                            }
                            array_push($nomenclatorKeyImages, $nomenclatorImage);
                        }
                    }
                    /** @var NomenclatorImage[] $nomenclatorKeyImages */
                   
                    if (sizeof($nomenclatorKeyImages) > 0) {
                        $urls=  [];
                        foreach ($nomenclatorKeyImages as $nomenclatorKeyImage) {
                            $urls[]= $nomenclatorKeyImage->url;
                        }
                        post_result([
                            'urls' => $urls
                        ]);
                    } else {
                        post_result(null);
                    }
                }
                break;
            default:
                throw new Exception('Method not allowed');
        }
    } catch (Exception $exception) {
        throwException($exception);
    }
}
