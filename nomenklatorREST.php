<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$pathRemove = 1;
$path = ltrim($_SERVER['REQUEST_URI'], '/');    // Trim leading slash(es)
$elements = explode('/', $path);                // Split path on slashes

for($i=0;$i<$pathRemove;$i++)
    array_shift($elements);
if(strcmp($elements[0],"images")===0)
{
    header("Location: ./images.php");
    die();
}
//var_dump($elements);
require_once ("entities/Database.php");
require_once ("config/DatabaseConfig.php");
try {

    $config = new DatabaseConfig();
    if ($config->getConnection() === null) {
       throw new Exception("Server error");
    }
    $database = new Database($config->getConnection());
    if ($database === null) {
        throw new Exception("Server error");
    }
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case "GET" :
            if (strcmp($elements[0], "nomenklators") === 0)
            {
                if (sizeof($elements) === 2)  // get nomenklators/{id}
                {
                    $nomenklator = $database->getNomenklatorById(intval($elements[1]));
                    if ($nomenklator === null)
                        throw new RuntimeException("Invalid nomenklator Id");
                    header('X-PHP-Response-Code: 200', true, 200);
                    echo json_encode($nomenklator);
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
                $simple = null;
                $homophonic = null;
                $bigrams = null;
                $trigrams = null;
                $codeBook = null;
                $nulls = null;
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
                        case "simple" :
                            if($simple === null)
                                $simple = filter_var($tmp[1],FILTER_VALIDATE_BOOLEAN);
                            else
                                throw new RuntimeException("Argument specified too many times");
                            break;
                        case "homophonic" :
                            if($homophonic === null)
                                $homophonic = filter_var($tmp[1],FILTER_VALIDATE_BOOLEAN);
                            else
                                throw new RuntimeException("Argument specified too many times");
                            break;
                        case "bigrams" :
                            if($bigrams === null)
                                $bigrams = filter_var($tmp[1],FILTER_VALIDATE_BOOLEAN);
                            else
                                throw new RuntimeException("Argument specified too many times");
                            break;
                        case "trigrams" :
                            if($trigrams === null)
                                $trigrams = filter_var($tmp[1],FILTER_VALIDATE_BOOLEAN);
                            else
                                throw new RuntimeException("Argument specified too many times");
                            break;
                        case "codeBook" :
                            if($codeBook === null)
                                $codeBook = filter_var($tmp[1],FILTER_VALIDATE_BOOLEAN);
                            else
                                throw new RuntimeException("Argument specified too many times");
                            break;
                        case "nulls" :
                            if($nulls === null)
                                $nulls = filter_var($tmp[1],FILTER_VALIDATE_BOOLEAN);
                            else
                                throw new RuntimeException("Argument specified too many times");
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
                $nomenklators = $database->getNomenklators($structure,$simple,$homophonic,$bigrams,$trigrams,
                $codeBook,$nulls,$folder);
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
            $id = null;
            if(isset($headers['authorization']))
            {
                $config = new DatabaseConfig();
                if($config !== null)
                {
                    $database = new Database($config->getConnection());
                    $id = $database->loginWithToken($headers['authorization']);
                    if($id === null)
                    {
                        throw new AuthorizationException("Invalid authorization token 8");
                    }

                }

            }
            else throw new AuthorizationException("No Authorization token");

            $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            if (stripos($content_type, 'application/json') === false) {
                throw new RuntimeException('Content-Type must be application/json');
            }
            $body = file_get_contents("php://input");
            $object = json_decode($body, true);
            if(strcmp($elements[0],"nomenklators") === 0)
            {
                //echo "Elements";
               if(sizeof($elements) === 1)
               {

                   if(!array_key_exists('signature',$object))
                    $object['signature']=null;
                   if(!array_key_exists('nulls',$object))
                       $object['nulls']=null;
                   if(!array_key_exists('codeBook',$object))
                       $object['codeBook']=null;
                   if(!array_key_exists('trigrams',$object))
                       $object['trigrams']=null;
                   if(!array_key_exists('bigrams',$object))
                       $object['bigrams']=null;
                   if(!array_key_exists('folder',$object))
                       $object['folder']=null;
                   if(!array_key_exists('images',$object))
                       $object['images']=null;

                   $addedId = $database->createNomenklator($object);
                   if($addedId === null)
                    throw new RuntimeException("Invalid body");
                   else
                   {
                       header('X-PHP-Response-Code: 200',true,200);
                       //header('Content-type: application/json');
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
                        $digitalizedTranscription->nomenklatorId = intval($elements[1]);
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
                           //header('Content-type: application/json');
                           $result['id'] = $addedId;
                           echo (json_encode($result));
                           die();
                       }
                   }
                   else throw new RuntimeException("Invalid URL 1");
               }
               else throw new RuntimeException("Invalid URL 2");
            }
            else if (strcmp($elements[0],"users"))
            {
                if($object['username'] !== null && $object['password'] !== null)
                {
                    if($database->addUser($object['username'],$object['password']) === null)
                    {
                        throw new Exception("Cannot Add new User");
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