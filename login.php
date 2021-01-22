<?php
require_once "entities/LoginInfo.php";
require_once "entities/Database.php";
require_once "config/DatabaseConfig.php";
require_once "entities/User.php";
try {


    $config = new DatabaseConfig();
    if($config!== null && $config->getConnection() !== null)
    {
        $database = new Database($config->getConnection());
        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
            throw new RuntimeException('Only POST requests are allowed');
        }
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (stripos($content_type, 'application/json') === false) {
            throw new RuntimeException('Content-Type must be application/json');
        }

        $body = file_get_contents("php://input");
        $object = json_decode($body, true);
        if (!is_array($object)) {
            throw new RuntimeException('Incorrect request body');
        }

        $username = $object['username'];
        $password = $object['password'];

        $id = $database->checkUser($username,$password);
        //echo ("ID IS " . $id);
        if($id !== null)
        {
            $token = $database->createToken($id);
           // echo "I am here <br>";

            $result['token'] = $token;
            //var_dump($result);
            header('X-PHP-Response-Code: 200',true,200);
            echo $result['token'];
            die();
        }
    }

}
catch (Exception $exception)
{
    if($exception instanceof RuntimeException)
    {
        header('X-PHP-Response-Code: 400',true,400);
        echo (json_encode($exception->getMessage()));
    }
    else
    {
        header('X-PHP-Response-Code: 500',true,500);
        echo (json_encode($exception->getMessage()));
    }
}



