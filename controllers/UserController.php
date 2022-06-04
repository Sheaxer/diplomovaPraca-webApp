<?php
require_once(__DIR__ ."/helpers.php");
require_once (__DIR__. "/../config/serviceConfig.php");
require_once (__DIR__ ."/../entities/SystemUser.php");
require_once (__DIR__ ."/../services/SystemUserService.php");
require_once (__DIR__ ."/../entities/AuthorizationException.php");
require_once (__DIR__ .'/MailerController.php');

function userController()
{
    $pathElements = getPathElements();
    $headers = apache_request_headers();
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "POST":
                $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
                $object = null;

                if (stripos($content_type, 'application/json') !== false) {
                    $object = json_decode(file_get_contents("php://input"), true);
                } else
                    throw new RuntimeException("Content type must be application/json");

                if (!array_key_exists("username", $object))
                    throw  new RuntimeException("No username");
                if (!array_key_exists("password", $object))
                    throw new RuntimeException("No password");
                if (strcmp($pathElements[0], "login") === 0) {
                    // LOGIN

                    if (sizeof($pathElements) !== 1)
                        throw new RuntimeException("Incorrect URL 3");

                    $systemUserService = POSTSystemUserService();

                    $userInfo = $systemUserService->logIn($object['username'], $object['password']);
                    if ($userInfo === null)
                        throw new RuntimeException("Incorrect credentials");
                    $data= $systemUserService->createToken($userInfo['id']);
                    if ($data) {
                        $data['isAdmin'] = boolval( $userInfo['isAdmin']);
                    }
                    post_result($data);

                } else if (strcmp($pathElements[0], "changePassword") === 0) {
                    if (sizeof($pathElements) !== 1)
                        throw new RuntimeException("Incorrect URL 2");

                    $systemUserService = POSTSystemUserService();

                    // CHANGE PASSWORD
                    if (!array_key_exists("newPassword", $object))
                        throw  new RuntimeException("No new password specified");
                    $userInfo = $systemUserService->logIn($object['username'], $object['password']);
                    if ($userInfo === null)
                        throw new RuntimeException("Incorrect credentials");
                    $systemUserService->changePassword($userInfo['id'], $object['newPassword']);
                    $data['message'] = "Password Changed";
                    post_result($data);


                } else if (strcmp($pathElements[0], "users") === 0) {
                    // ADD USER
                    $userInfo = authorize();
                    if (! $userInfo || ! $userInfo['isAdmin']) {
                        throw new AuthorizationException('Only admin can create new users');
                    }
                    $systemUserService = POSTSystemUserService();
                    $addedId = $systemUserService->createSystemUser($object['username'], $object['password'], $object['isAdmin'] ?? false);
                    if ($addedId !== null) {
                        $data['id'] = $addedId;
                        post_result($data);
                    } else
                        throw new Exception("System Error");
                } else if (strcmp($pathElements[0], "register") === 0) {
                    //$userInfo = authorize();

                    $systemUserService = GETSystemUserService();

                    $userId = $systemUserService->getUserIdByUsername($object['username']);
                    if ($userId) {
                        throw new Exception('User already exists');
                    }

                    $systemUserService = POSTSystemUserService();
                    $addedId = $systemUserService->createSystemUser($object['username'], $object['password'], false);
                    if ($addedId !== null && ! is_array($addedId) ) {
                        sendMail('New user registration', 'New user ' . $object['username'] . ' registered on keys.hcportal.eu.' );
                        $data['id'] = $addedId;
                        post_result($data);
                    } else {
                        if (is_array($addedId)) {
                           returnExceptionArray($addedId);
                        }
                    }
                        throw new Exception("System Error");
                }
                else throw new RuntimeException("Incorrect URL 1");
        }
    } catch (Exception $exception) {
        throwException($exception);
    }
}
