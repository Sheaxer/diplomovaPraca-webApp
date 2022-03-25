<?php

require_once(__DIR__ ."/helpers.php");
require_once (__DIR__ . "/../config/serviceConfig.php");
require_once (__DIR__ ."/../entities/NomenclatorImage.php");


function imageController()
{
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

                    $object = getData();

                    $nomenclatorKeyImages = array();
                    $uploadedUrl = [];
                    $encodedUrl = [];
                    $i = 0;
                    foreach ($object as $image) {
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

                        array_push($nomenclatorKeyImages, $nomenclatorImage);
                    }

                    post_result($nomenclatorKeyImages);
                }
                break;
            default:
                throw new Exception('Method not allowed');
        }
    } catch (Exception $exception) {
        throwException($exception);
    }
}
