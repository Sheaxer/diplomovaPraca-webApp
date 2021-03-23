<?php
require_once("NomenclatorKey.php");
require_once("NomenclatorImage.php");
require_once("AuthorizationException.php");
require_once ("LoginInfo.php");
require_once("SystemUser.php");
require_once("NomenclatorFolder.php");
require_once ("EncryptionPair.php");
require_once("controllers/helpers.php");
class Database{

	public ?PDO $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    function getUnasignedImages(): array
    {
        $query = "SELECT url FROM " . NomenclatorImage::$tableName . " WHERE nomenklatorId IS NULL";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $res= $stm->fetchAll();
        $ret = [];
        foreach ($res as $i)
        {
            array_push($ret,$i['url']);
        }
        return $ret;
    }

    function addOrModifyImage(string $url, ?int $nomenklatorKeyId, ?int $order, bool $isLocal, ?string $structure)
    {
        $query = "SELECT 1 FROM " . NomenclatorImage::$tableName . " WHERE url=:url";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':url',$url);
        $stm->execute();
        if ($stm->fetch() === false)
        {
            $query2 = "INSERT INTO " . NomenclatorImage::$tableName . "(url, nomenklatorKeyId, `order`, isLocal, structure)
            VALUES (:url,:nomenklatorKeyId,:ord,:isLocal,:structure)";
        }
        else
        {
            $query2 = "UPDATE " . NomenclatorImage::$tableName . " SET nomenklatorKeyId = :nomenklatorKeyId, `order`= :ord, 
            isLocal = :isLocal, structure = :structure WHERE url=:url";
        }
        $stm2 = $this->conn->prepare($query2);
        $stm2->bindParam(':url',$url);
        $stm2->bindParam(':nomenklatorKeyId',$nomenklatorKeyId);
        $stm2->bindParam(':ord',$order);
        $stm2->bindParam(':isLocal',$isLocal);
        $stm2->bindParam(':structure',$structure);
        $stm2->execute();

    }

    function getEncryptionPairsByTranscriptionId(int $id): ?array
    {
        $query = "SELECT plainTextUnit, cipherTextUnit FROM " . EncryptionPair::$tableName .
            " WHERE digitalizedTranscriptionId = :id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id', $id);
        $stm->execute();
        $data = $stm->fetchAll(PDO::FETCH_ASSOC);
        if ($data === null | $data === false)
            return null;
        return $data;
    }

    function getEncryptionKeyByTranscriptionId(int $id): ?array
    {
        $data = $this->getEncryptionPairsByTranscriptionId($id);
        if($data === null)
            return null;
        $result = array();
        foreach ($data as $d)
        {
            if(!array_key_exists($d["plainTextUnit"],$result))
            {
               $result[$d["plainTextUnit"]] = array();
            }
            array_push($result[$d["plainTextUnit"]], $d["cipherTextUnit"]);
        }
        return $result;
    }

    function getDecryptionKeyByTranscriptionId(int $id): ?array
    {
        $data = $this->getEncryptionPairsByTranscriptionId($id);
        if($data === null)
            return null;
        $result = array();
        foreach ($data as $d)
        {
            $result[$d["cipherTextUnit"]] = $d["plainTextUnit"];
        }
        return $result;
    }

    function getNomenklators(?array $completeStructure = null,
                              ?array $folders = null): array
    {
        $query = "SELECT * FROM "
            . NomenclatorKey::$table_name;
        $parameters = array();
        if($completeStructure !== null)
        {

            $query = $query . "WHERE ";
            if(count($completeStructure) === 1)
            {
                $parameters["completeStructure"] = $completeStructure[1];
                $query = $query . "completeStructure=:completeStructure";
            }
            else
            {
                $s="";
                $i = 0;
                foreach ($completeStructure as $item)
                {
                    $key = ":s".$i++;
                    $s .= "$key,";
                    $parameters[$key] = $item;
                }
                $s = rtrim($s,",");
                $query = $query . "completeStructure IN ($s)";
            }

        }
        if($folders !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            if(count($folders) === 1)
            {
                $query = $query . " folder=:folder";
            }
            else
            {
                $s="";
                $i = 0;
                foreach ($folders as $item)
                {
                    $key = ":f".$i++;
                    $s .= "$key,";
                    $parameters[$key] = $item;
                }
                $s = rtrim($s,",");
                $query = $query . "folder IN ($s)";
            }
        }
        $stm = $this->conn->prepare($query);
        $stm->execute($parameters);
        $data = $stm->fetchAll(PDO::FETCH_CLASS,"Nomenklator");

        foreach ($data as $n)
        {
            if($n instanceof NomenclatorKey)
            {
                $q = "SELECT id, digitalizationVersion, note, digitalizationDate, createdBy FROM " .
                    DigitalizedTranscription::$tableName . " WHERE nomenklatorKeyId =:nomenklatorKeyId";// fetch digitalized transcriptions
                $stm2 = $this->conn->prepare($q);
                $stm2->bindParam(':nomenklatorKeyId',$n->id);
                $stm2->execute();
                $n->digitalizedTranscriptions = $stm2->fetchAll(PDO::FETCH_CLASS,"DigitalizedTranscription");

                $q2 = "SELECT url from ". NomenclatorImage::$tableName . "WHERE nomenklatorKeyId=:nomenklatorKeyId";
                $stm3 = $this->conn->prepare($q2);
                $stm3->bindParam(':nomenklatorKeyId',$n->id);
                $stm3->execute();
                $n->images = $stm3->fetchAll(PDO::FETCH_COLUMN);
            }
        }

        return $data;
    }

    public function getNomenklatorById(int $id) : ?NomenclatorKey
    {
        $query = "SELECT * FROM "
            . NomenclatorKey::$table_name . " WHERE id=:id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$id,PDO::PARAM_INT);
        $stm->execute();
        $data = $stm->fetchObject("NomenclatorKey");
        if($data instanceof NomenclatorKey)
        {
            $q = "SELECT url, structure from ". NomenclatorImage::$tableName . " ORDER BY `order` ASC WHERE nomenklatorKeyId=:nomenklatorKeyId";
            $stm2 = $this->conn->prepare($q);
            $stm2->bindParam(':nomenklatorKeyId',$id);
            $stm2->execute();
            $images = $stm2->fetchAll(PDO::FETCH_COLUMN);
            if($images !== false) {
                $data->images = $images;
            }
            $q2 = "SELECT id, digitalizedVersion, note, digitalizationDate, createdBy FROM " .
                DigitalizedTranscription::$tableName . " WHERE nomenklatorId =:nomenklatorId";// fetch digitalized transcriptions
            $stm3 = $this->conn->prepare($q2);
            $stm3->bindParam(':nomenklatorId',$id);
            $stm3->execute();

            $transcriptions  = $stm3->fetchAll(PDO::FETCH_CLASS,"DigitalizedTranscription");
            if($transcriptions !== false) {
                $data->digitalizedTranscriptions = $transcriptions;
            }

            return $data;
        }
        return null;
    }

    public function createNomenclator(array $nomenclator): ?int
    {
        try {
            $query = "SELECT 1 from " . NomenclatorImage::$tableName . " WHERE url=:url";
            $stm = $this->conn->prepare($query);
            foreach ($nomenclator['images'] as $image)
            {
                $stm->bindParam(':url',$image);
                $stm->execute();
                $r = $stm->fetch();
                if($r !== false )
                {
                    throw new Exception("Image already belongs to another nomenklator");
                }
            }
            $query = "INSERT INTO " . NomenclatorKey::$table_name . " ( folder, finalStructure) values (:folder, :finalStructure)";
            $stm = $this->conn->prepare($query);
            $stm->bindParam(":folder",$nomenclator['folder']);
            $stm->bindParam(":finalStructure",$nomenclator['structure']);
            $this->conn->beginTransaction();

            //var_dump( $nomenklator);
            $ans = $stm->execute();
            //echo var_dump($ans);
            if ($ans)
            {
                $id = $this->conn->lastInsertId();
                $i = 1;
                foreach ($nomenclator['images'] as $image)
                {
                    $q = "INSERT INTO " . NomenclatorImage::$tableName .
                        " (url,`order`,nomenklatorId) VALUES (:url,:ord,:nomenklatorId)";
                    $stm2 = $this->conn->prepare($q);
                    $stm2->bindParam(':url', $image);
                    $stm2->bindParam(':ord', $i);
                    $stm2->bindParam(':nomenklatorId', $id);
                    $stm2->execute();
                    $i++;
                }
                $this->conn->commit();
                return $id;
            }
        }catch (Exception $e)
        {
            echo $e->getMessage();
            $this->conn->rollBack();
        }
       return null;
    }

    public function createDigitalizedTranscription(DigitalizedTranscription $digitalizedTranscription): ?int
    {
        $query = "INSERT INTO " . DigitalizedTranscription::$tableName . " (nomenklatorId, digitalizaionVersion,
        note, digitalizaionDate, createdBy) VALUES (:nomenklatorId, :digitalizaionVersion, :note, :digitalizationDate,
        :createdBy)";
        $stm = $this->conn->prepare($query);

        $this->conn->beginTransaction();
        try {
            $stm->execute($digitalizedTranscription);
            $id = $this->conn->lastInsertId();

            $q = "INSERT INTO encryptionPairs (digitalizedTranscriptionId, opeText, cipherText) VALUES (:id,:openText,:cipherText)";
            $stm2 = $this->conn->prepare($q);

            foreach ($digitalizedTranscription->encryptionPairs as $cipherText => $openText)
            {
                $stm2->bindParam(":id",$id);
                $stm2->bindParam(":openText",$openText);
                $stm2->bindParam(":cipherText",$cipherText);
                $stm2->execute();
            }
            $this->conn->commit();
            return $id;

        }catch(Exception $e)
        {
            $this->conn->rollBack();
        }
        return null;

    }

    public function checkUser(string $username, string $password): ?int
    {
        $query = "SELECT id,username, passwordHash from " . SystemUser::$tableName . " WHERE username=:username";
        $stm=$this->conn->prepare($query);
        $stm->bindParam(':username',$username);
        $stm->execute();
        $data = $stm->fetchObject("SystemUser");
        if($data instanceof SystemUser)
        {

            // HERE IS PASSWORD VERIFICATION
            if(password_verify($password,$data->passwordHash))
            {
                return $data->id;
            }
            else
            {
                return null;
            }
            //return $data->id;
        }
        return null;
    }

    public function getFolders(): ?array
    {
        $query = "SELECT `name`,fond FROM " . NomenclatorFolder::$tableName;
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $data = $stm->fetchAll(PDO::FETCH_CLASS,"NomenclatorFolder");
        if($data)
            return $data;
        return null;
    }

    public function addUser(string $username, string $password): ?int
    {
        $query = "SELECT 1 from " . SystemUser::$tableName . " WHERE username=:username";
        $stm=$this->conn->prepare($query);
        $stm->bindParam(':username',$username);
        $stm->execute();
        $data = $stm->fetch(PDO::FETCH_ASSOC);
        if(!$data)
        {
            $q = "INSERT INTO " . SystemUser::$tableName . "(username, passwordHash) VALUES (:username,:passwordHash)";
            $stm2=$this->conn->prepare($q);
            $stm2->bindParam(':username',$username);
            $hash  = password_hash($password,PASSWORD_DEFAULT);
            $stm2->bindParam(':passwordHash',$hash);
            $stm2->execute();
            return $this->conn->lastInsertId();
        }
        return null;
    }

    public function loginWithToken(string $tokenString) : ?int
    {
        //echo $tokenString;
        //echo $tokenString;
        //$a= strpos($tokenString,":" );
        //var_dump($a);
        if (strpos($tokenString,":" ) === false) {
            throw new AuthorizationException('Invalid authentication token 4');
        }
        list($tokenLeft, $tokenRight) = explode(':', $tokenString);
        if ((strlen($tokenLeft) !== 20) || (strlen($tokenRight) !== 44)) {
            throw new AuthorizationException('Invalid authentication token 3');
        }
        $tokenRightHashed = hash('sha256', $tokenRight);

        $stmt = $this->conn->prepare("SELECT userId,hash,expiresAt FROM " . LoginInfo::$table_name . " WHERE selector = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'LoginInfo');
        $stmt->execute([$tokenLeft]);
        $info = $stmt->fetch();
        //echo var_dump($row);
        if($info instanceof LoginInfo)
        {
            if(hash_equals($info->hash,$tokenRightHashed))
            {
                try {
                    $expires_date = new DateTime($info->expiresAt);
                    $current_date = new DateTime('now');
                    if($expires_date > $current_date)
                    {
                        //echo var_dump($row['userId']);
                        return $info->userId;
                    }
                } catch (Exception $e) {
                    throw new AuthorizationException('Invalid authentication token 1');
                }
            }
            else throw new AuthorizationException('Invalid authentication token 2');
           // return null;
        }
        else throw new AuthorizationException("Invalid authentication token 5");
        return null;
    }

    public function createToken(int $userId): ?array
    {
        try {
            $tokenLeft = base64_encode(random_bytes(15));
            $tokenRight = base64_encode(random_bytes(33));
            $tokenRightHashed = hash('sha256', $tokenRight);

            $date = new DateTime('now');

            $expire_date = new DateTime('now');
            $expire_date->add(new DateInterval("PT". strval(LoginInfo::getHoursToExpire()) . "H"));
            $stmt = $this->conn->prepare("INSERT INTO ". LoginInfo::$table_name .
                " (userId,selector,hash,loginDate,expiresAt) VALUES
            (?,?,?,?,?)");

            $stmt->execute([$userId,$tokenLeft,$tokenRightHashed,$date->format("Y-m-d H:i:s"),$expire_date->format("Y-m-d H:i:s")]);
            $res['token'] = $tokenLeft.':'.$tokenRight;
            $res['expiresAt'] = $expire_date->format("Y-m-d H:i:s");
            return $res;

        } catch (Exception $e) {

        }
        return null;
    }

}


