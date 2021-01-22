<?php
$headers = apache_request_headers();
$fileSizeLimit = 1000000;
$uploadFolder = "/diplomovka/images/";
require_once("entities/Database.php");
require_once ("config/DatabaseConfig.php");
try {
    $id = null;
    if(isset($headers['Authorization']))
    {
        $config = new DatabaseConfig();
        if($config !== null)
        {
            $database = new Database($config->getConnection());
            $database->loginWithToken($headers['Authorization']);
            if($id === null)
                throw new AuthorizationException("Invalid authorization token");
        }

    }
    else throw new AuthorizationException("No Authorization token");


    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (
        !isset($_FILES['nomenklatorImage']['error']) ||
        is_array($_FILES['nomenklatorImage']['error'])
    ) {
        throw new RuntimeException('Invalid parameters.');
    }

    // Check $_FILES['nomenklatorImage']['error'] value.
    switch ($_FILES['nomenklatorImage']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    // You should also check filesize here.
    if ($_FILES['nomenklatorImage']['size'] > $fileSizeLimit) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
            $finfo->file($_FILES['nomenklatorImage']['tmp_name']),
            array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png'
            ),
            true
        )) {
        throw new RuntimeException('Invalid file format.');
    }

    if (!move_uploaded_file(
        $_FILES['nomenklatorImage']['tmp_name'],
        sprintf('./images/%s.%s',
            $_FILES['nomenklatorImage']['name'],
            $ext
        )
    )) {
        throw new Exception('Failed to move uploaded file.');
    }
    else
    {
        $response['url'] =
        substr($_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"],0,-4) . "/" . $_FILES['nomenklatorImage']['name'] ;
        header('X-PHP-Response-Code: 200',true,200);
        echo json_encode($response);
    }


}
catch (Exception $exception)
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
