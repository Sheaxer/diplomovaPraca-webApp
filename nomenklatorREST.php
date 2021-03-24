<?php
require_once (__DIR__ . "/controllers/DigitalizedTranscriptionController.php");
require_once (__DIR__ . "/controllers/NomenclatorKeysController.php");
require_once (__DIR__ . "/controllers/UserController.php");
require_once (__DIR__. "/controllers/helpers.php");
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
    echo "TODO";
// required headers
//header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");
//$pathRemove = 1;
//$path = ltrim($_SERVER['REQUEST_URI'], '/');    // Trim leading slash(es)
//$elements = explode('/', $path);                // Split path on slashes

//for($i=0;$i<$pathRemove;$i++)
  //  array_shift($elements);
/*if(strcmp($elements[0],"images")===0)
{
    header("Location: ./nomenclators.php");
    die();
}*/
//var_dump($elements);
//require_once ("entities/Database.php");
//require_once("config/DatabaseConfig.php");
//require_once ("config/constants.php");
//require_once("controllers/helpers.php");
//$id=null;

/*try {

    $config = new POSTDatabaseConfig();
    if ($config->getConnection() === null) {
       throw new Exception("Server error");
    }
    $database = new Database($config->getConnection());
    if ($database === null) {
        throw new Exception("Server error");
    }

    //var_dump($database->getUnasignedImages());

    switch ($_SERVER['REQUEST_METHOD'])
    {
        case "GET" :

            if (strcmp($elements[0], "nomenklatorKeys") === 0)
            {
                if (sizeof($elements) === 2)  // get nomenklators/{id}
                {
                    $nomenklatorKey = $database->getNomenklatorById(intval($elements[1]));
                    if ($nomenklatorKey === null)
                        throw new RuntimeException("Invalid nomenklator Id");
                    header('X-PHP-Response-Code: 200', true, 200);
                    echo json_encode($nomenklatorKey);
                    die();
                } else if (sizeof($elements) === 1)  //get nomenklators
                {
                    $nomenklators = $database->getNomenklators();
                    if ($nomenklators === null) {
                        header('X-PHP-Response-Code: 204', true, 204);
                        die();
                    }
                    header('X-PHP-Response-Code: 200', true, 200);
                    header('Content-type: application/json');
                    echo(json_encode($nomenklators));
                    die();
                } else
                    throw new RuntimeException("Invalid URI");
            }
            else if(substr_compare($elements[0],"nomenklators?",0) === 0) // get nomenklators?[arguments]
            {
                if (sizeof($elements) !== 1)
                    throw new RuntimeException("Invalid URL 4");
                $arguments = explode(substr($elements[0],13),"&");
                $structure = null;
                $folder = null;
                foreach ($arguments as $argument)
                {
                    $tmp = explode($argument,'=');
                    if(count($tmp) !== 2)
                        throw new RuntimeException("Invalid arguments");
                    switch ($tmp[0])
                    {
                        case "structure" :
                            if($structure === null)
                                $structure = array();
                            $structure[] = urldecode($tmp[1]);
                            break;

                        case "folder":
                            if($folder === null)
                                $folder = array();
                            $folder[] = urldecode($tmp[1]);
                            break;
                        default:
                            throw new RuntimeException("Invalid argument");
                    }
                }
                $nomenklators = $database->getNomenklators($structure,$folder);
                if($nomenklators === null)
                {
                    header('X-PHP-Response-Code: 204',true,204);
                    die();
                }
                header('X-PHP-Response-Code: 200',true,200);
                header('Content-type: application/json');
                echo (json_encode($nomenklators));
                die();
            }
            else if(strcmp($elements[0],"digitalTranscriptions") === 0)
            {
                    if(sizeof($elements) !== 3)
                        throw new RuntimeException("Invalid URL 5");
                    switch ($elements[2])
                    {
                        case "encryptionKey":
                            $result = $database->getEncryptionKeyByTranscriptionId(intval($elements[1]));
                            if($result === null)
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
                        case "decryptionKey":
                            $result = $database->getDecryptionKeyByTranscriptionId(intval($elements[1]));
                            if($result === null)
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
                        default:
                            throw new RuntimeException("Invalid URL 6");
                    }
            }
            else if(strcmp($elements[0],"folders") === 0)
            {
                $folders = $database->getFolders();
                if($folders === null)
                {
                    header('X-PHP-Response-Code: 204',true,204);
                    die();
                }
                else
                {
                    header('X-PHP-Response-Code: 200',true,200);
                    header('Content-type: application/json');
                    echo json_encode($folders);
                }
            }

            break;
        case "POST":
            $headers = apache_request_headers();
            header('X-PHP-Response-Code: 200',true,200);
            header('Content-type: text/plain');

            $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            $object = null;

            if (stripos($content_type, 'application/json') !== false) {
                $object = json_decode(file_get_contents("php://input"), true);
            }
            else if (stripos($content_type,'multipart/form-data') !== false)
            {
                $object = json_decode($_POST['data'],true);
            }
            else
                throw new RuntimeException('Invalid request content type');
            $id = null;
            if(strcmp($elements[0],"login") === 0)
            {

                //var_dump($body);

                $validUser = $database->checkUser($object['username'],$object["password"]);
                if($validUser !== null)
                {
                    $result = $database->createToken($validUser);
                    header('X-PHP-Response-Code: 200',true,200);
                    header('Content-type: application/json');
                    echo (json_encode($result));
                    die();
                }
                else
                {
                    throw new RuntimeException("Invalid credentials");

                }
            }

            $id=authorize($database);


            if(strcmp($elements[0],"nomenklatorKeys") === 0)
            {
                //echo "Elements";
               if(sizeof($elements) === 1)
               {
                   if(!array_key_exists('signature',$object))
                       throw new RunTimeException('Incorrect nomenklatorKey');

                   $object['uploadedBy'] = $id;
                   $addedId = $database->createNomenclator($object);
                   if($addedId === null)
                    throw new RuntimeException("Invalid body");
                   else
                   {
                       header('X-PHP-Response-Code: 200',true,200);
                       header('Content-type: application/json');
                       $result['id'] = $addedId;
                       echo (json_encode($result));
                       die();
                   }



               }
               else if(sizeof($elements) === 3)
               {
                   if(strcmp($elements[2],"digitalTranscriptions") == 0)
                   {
                        $digitalizedTranscription = new DigitalizedTranscription();
                        $digitalizedTranscription->nomenclatorKeyId = intval($elements[1]);
                        $digitalizedTranscription->createdBy = $id;
                        $digitalizedTranscription->digitalizationDate = new DateTime();
                        $digitalizedTranscription->note = $object['note'];
                        $digitalizedTranscription->encryptionPairs = $object['encryptionPairs'];

                        $addedId = $database->createDigitalizedTranscription($digitalizedTranscription);
                       if($addedId === null)
                           throw new RuntimeException("Invalid body");
                       else
                       {
                           header('X-PHP-Response-Code: 200',true,200);
                           header('Content-type: application/json');
                           $result['id'] = $addedId;
                           echo (json_encode($result));
                           die();
                       }
                   }
                   else throw new RuntimeException("Invalid URL 1");
               }
               else throw new RuntimeException("Invalid URL 2");
            }
            else if (strcmp($elements[0],"users") == 0)
            {
                if($object['username'] !== null && $object['password'] !== null)
                {
                    if($database->addUser($object['username'],$object['password']) === null)
                    {
                        throw new Exception("Cannot Add new SystemUser");
                    }
                    else
                    {
                        header('X-PHP-Response-Code: 204',true,204);
                        die();
                    }
                }
            }

            break;
        default:
            throw new RuntimeException("Invalid URL 3");
    }
}
catch (Exception $exception)
{
    throwException($exception);
}
*/
