<?php

require_once (__DIR__ ."/helpers.php");
require_once (__DIR__ ."/../config/serviceConfig.php");
require_once (__DIR__ . "/../services/KeyUserService.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");

function keyUsersController()
{
    try {
        switch ($_SERVER['REQUEST_METHOD'])
        {
            case "GET":
                $pathElements = getPathElements();
                $keyUserService = GETKeyUserService();
                if(sizeof($pathElements) === 1)
                {
                    if(strcmp(substr($pathElements[0],0, 8),"keyUsers") === 0)
                    {
                        if(isset($_GET['name']) && !empty($_GET['name']) && ($_GET['name'] !== null)){
                            $user = $keyUserService->getKeyUserByName($_GET['name']);
                            post_result(stripNullsFromObject($user));
                            exit();
                        }
                        $keyUsers = $keyUserService->getAllKeyUsers();
                        post_result(stripNullsFromArray($keyUsers));
                        exit();
                    }

                }
                else if (sizeof($pathElements) === 2)
                {
                    if(is_numeric($pathElements[1]))
                    {
                        $user = $keyUserService->getKeyUserById(intval($pathElements[1]));
                        post_result($user);
                        exit();
                    }
                }


                break;
            case "POST":
                $userInfo = authorize();
                if (! $userInfo) {
                    throw new AuthorizationException('Not authorized');
                }
                $object = getData();
                if($object === null)
                    throw new RuntimeException("No data provided");
                $keyUser = new KeyUser();
                if(array_key_exists("name",$object))
                    $keyUser->name = $object['name'];
                else
                    throw new Exception("No name specified for key user");
                $keyUserService = POSTKeyUserService();
                $newId = $keyUserService->createKeyUser($keyUser);

                $res['id'] = $newId;
                post_result($res);

                break;
            default:
                throw new Exception("INVALID REQUEST METHOD");
        }
    }
    catch (Exception $e)
    {
        throwException($e);
    }
}