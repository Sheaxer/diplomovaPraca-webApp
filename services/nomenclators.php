<?php

$headers = apache_request_headers();
$fileSizeLimit = 1000000;
require_once("entities/Database.php");
require_once("entities/NomenclatorImage.php");
require_once("config/DatabaseConfig.php");
require_once("config/constants.php");
require_once("helpers.php");

function uploadNomenclator(Database  $database, int $id, $nomenclator)
{

}

try {

    $config = new POSTDatabaseConfig();
    if ($config->getConnection() === null) {
        throw new Exception("Server error");
    }
    $database = new Database($config->getConnection());
    if ($database === null) {
        throw new Exception("Server error");
    }

    if($_SERVER['REQUEST_METHOD'] === "GET")
    {

        header('X-PHP-Response-Code: 200',true,200);
        header('Content-type: application/json');
         echo(json_encode($database->getUnasignedImages()));
        die();
    }

    $id = authorize($database);
    // Undefined | Multiple Files | $_FILES Corruption Attack
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
    //var_dump($finfo->file($_FILES['nomenklatorImage']['tmp_name']));
    $ext = array_search(
        $finfo->file($_FILES['nomenklatorImage']['tmp_name']),
        array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png'
        ),
        true
    );
    if (false === $ext ) {
        throw new RuntimeException('Invalid file format.');
    }

    if (!move_uploaded_file(
        $_FILES['nomenklatorImage']['tmp_name'],
        sprintf( NomenclatorImage::$uploadFolder . '%s.%s',
            pathinfo($_FILES['nomenklatorImage']['name'])['filename'],
            $ext
        )
    )) {
        throw new Exception('Failed to move uploaded file.');
    }
    else
    {
        /*$response['url'] =
        substr($_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"],0,-4) . "/" . $_FILES['nomenklatorImage']['name'] ;*/
        $response['url'] = sprintf( SERVICEPATH. NomenclatorImage::$uploadFolder. '%s.%s',
            pathinfo($_FILES['nomenklatorImage']['name'])['filename'],
            $ext
        );
        $database->addOrModifyImage($response['url'],null,null,true,null);

        header('X-PHP-Response-Code: 200',true,200);
        header('Content-type: application/json');
        echo json_encode($response);
    }


}
catch (Exception $exception)
{
    throwException($exception);
}
