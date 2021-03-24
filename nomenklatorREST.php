<?php
require_once (__DIR__. "/controllers/helpers.php");
require_once (__DIR__ . "/controllers/DigitalizedTranscriptionController.php");
require_once (__DIR__ . "/controllers/NomenclatorKeysController.php");
require_once (__DIR__ . "/controllers/UserController.php");

require_once (__DIR__ . "/controllers/FolderController.php");
require_once (__DIR__ . "/controllers/KeyUsersController.php");
require_once (__DIR__ . "/controllers/CipherCreatorController.php");
$path = getPathElements();


if (strcmp(substr($path[0], 0, 15), "nomenclatorKeys") === 0)
    nomenclatorKeyController();
else if (strcmp($path[0], "digitalizedTranscriptions") === 0)
    digitalizedTranscriptionController();
else if( (strcmp($path[0],"login") === 0) || (strcmp($path[0],"users") === 0) || (strcmp($path[0], "changePassword") === 0))
    userController();
else if (strcmp($path[0],"folders") === 0)
    folderController();
else if(strcmp(substr($path[0],0,8),"keyUsers") === 0)
    keyUsersController();
else
    if(strcmp($path[0],"cipherCreator") === 0)
        cipherCreatorController();
