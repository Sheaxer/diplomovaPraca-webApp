<?php
require_once (__DIR__ . "/../config/constants.php");
require_once (__DIR__ . "/../config/serviceConfig.php");

function authorize() : ?array
{
    $headers = apache_request_headers();
    if(isset($headers['authorization']))
    {
        $systemUserService = GETSystemUserService();
        if($systemUserService === null)
        {
            throw new Exception("System Error");
        }
        if ($headers['authorization']) {
            $userInfo = $systemUserService->loginWithToken($headers['authorization']);
            if($userInfo === null)
            {
                throw new AuthorizationException("Invalid authorization token 8");
            }
            return $userInfo;
         } else return null;   
        

    }
    else return null;
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

function checkForLongUnicode($string)
{
    $tmp_str = $string;
    $tmp_str_left = "";
    while(strlen($tmp_str ) > 0)
    {
        if (mb_ord($tmp_str) > 65535)
        {

            $s = mb_ord($tmp_str);
            $u = intval(floor(($s - 0x10000) / 0x400) + 0xD800);
            $l = (($s - 0x10000) % 0x400) + 0xDC00;
            //$uc = json_decode('"\u' . dechex($u). '"');
            //var_dump($uc);
            $uc = "\u" . dechex($u) . "\u" . dechex($l);
            $tmp_str_left .= $uc;
            $tmp_str = substr($tmp_str,4);
        }
        else
        {
            $tmp_str_left .= substr($tmp_str,0,1);
            $tmp_str = substr($tmp_str,1);
        }
    }

    return $tmp_str_left;
}

