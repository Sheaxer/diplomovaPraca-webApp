<?php
require_once (__DIR__ . "/../config/constants.php");
require_once (__DIR__ . "/../config/serviceConfig.php");

function authorize() :?int
{
    $headers = apache_request_headers();
    if(isset($headers['authorization']))
    {
        $systemUserService = GETSystemUserService();
        if($systemUserService === null)
        {
            throw new Exception("System Error");
        }
        $id = $systemUserService->loginWithToken($headers['authorization']);
        if($id === null)
        {
            throw new AuthorizationException("Invalid authorization token 8");
        }
        return $id;

    }
    else throw new AuthorizationException("No Authorization token");
}

function getData(): ?array
{
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $object = null;

    if (stripos($content_type, 'application/json') !== false) {
        $object = json_decode(file_get_contents("php://input"), true);
    } else if (stripos($content_type, 'multipart/form-data') !== false) {
        $object = json_decode($_POST['data'], true);
    }
    return $object;
}

function getPathElements(): array
{
    $path = ltrim($_SERVER['REQUEST_URI'], '/');    // Trim leading slash(es)
    $elements = explode('/', $path);                // Split path on slashes
    for($i=0;$i<PATHREMOVE;$i++)
        array_shift($elements);
    return $elements;
}

function throwException(Exception $exception)
{
    $errors['error'] = $exception->getMessage();
    if($exception instanceof AuthorizationException)
    {
        header('X-PHP-Response-Code: 401',true,401);
        echo (json_encode($errors));
    }
    else if($exception instanceof RuntimeException)
    {
        header('X-PHP-Response-Code: 400',true,400);
        echo (json_encode($errors));
    }
    else
    {
        header('X-PHP-Response-Code: 500',true,500);
        echo (json_encode($errors));
    }
}

function post_result($result)
{
    if($result === null || empty($result))
    {
        header('X-PHP-Response-Code: 204',true,204);
        die();
    }
    else
    {
        header('X-PHP-Response-Code: 200',true,200);
        header('Content-type: application/json');
        echo (json_encode($result));
        die();
    }
}

function generateRandomString($length = 10): string {
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}