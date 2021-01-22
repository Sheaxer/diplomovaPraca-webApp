<?php
require_once("Nomenklator.php");
require_once("NomenklatorImage.php");
require_once("AuthorizationException.php");
require_once ("LoginInfo.php");
class Database{

	public ?PDO $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }



    function getEncryptionPairsByTranscriptionId(int $id): ?array
    {
        $query = "SELECT openText, cipherText FROM encryptionPairs WHERE digitalizedTranscriptionId = :id";
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
            if(!array_key_exists($d["openText"],$result))
            {
               $result[$d["openText"]] = array();
            }
            array_push($result[$d["openText"]], $d["cipherText"]);
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
            $result[$d["cipherText"]] = $d["openText"];
        }
        return $result;
    }

    function getNomenklators(?array $structure = null,
                             ?bool $simple = null, ?bool $homophonic = null, ?bool $bigrams = null, ?bool $trigrams = null,
                             ?bool $codeBook = null, ?bool $nulls = null, ?array $folders = null): array
    {
        $query = "SELECT * FROM "
            . Nomenklator::$table_name;
        $parameters = array();
        if($structure !== null)
        {

            $query = $query . "WHERE ";
            if(count($structure) === 1)
            {
                $parameters["structure"] = $structure[1];
                $query = $query . "structure=:structure";
            }
            else
            {
                $s="";
                $i = 0;
                foreach ($structure as $item)
                {
                    $key = ":s".$i++;
                    $s .= "$key,";
                    $parameters[$key] = $item;
                }
                $s = rtrim($s,",");
                $query = $query . "structure IN ($s)";
            }

        }
        if($simple !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            $parameters["simple"] = $simple;
            $query = $query . "`simple`=`:simple`";
        }
        if($homophonic !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            $parameters["homophonic"] = $homophonic;
            $query = $query . "homophonic=:homophonic";
        }
        if($bigrams !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            $parameters["bigrams"] = $bigrams;
            $query = $query . "bigrams=:bigrams";
        }
        if($trigrams !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            $parameters["trigrams"] = $trigrams;
            $query = $query . "trigrams=:trigrams";
        }
        if($codeBook !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            $parameters["codeBook"] = $codeBook;
            $query = $query . "codeBook=:codeBook";
        }
        if($nulls !== null)
        {
            if(empty($parameters))
            {
                $query = $query . "WHERE ";
            }
            else
            {
                $query = $query ." AND ";
            }
            $parameters["nulls"] = $nulls;
            $query = $query . "`nulls`=`:nulls`";
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
            if($n instanceof Nomenklator)
            {
                $q = "SELECT id, digitalizationVersion, note, digitalizationDate, createdBy FROM " .
                    DigitalizedTranscription::$tableName . " WHERE nomenklatorId =:nomenklatorId";// fetch digitalized transcriptions
                $stm2 = $this->conn->prepare($q);
                $stm2->bindParam(':nomenklatorId',$n->id);
                $stm2->execute();
                $n->digitalizedTranscriptions = $stm2->fetchAll(PDO::FETCH_CLASS,"DigitalizedTranscription");

                $q2 = "SELECT url from ". NomenklatorImage::$tableName . " ORDER BY `order` ASC WHERE nomenklatorId=:nomenklatorId";
                $stm3 = $this->conn->prepare($q2);
                $stm3->bindParam(':nomenklatorId',$n->id);
                $stm3->execute();
                $n->images = $stm3->fetchAll(PDO::FETCH_COLUMN);
            }
        }

        return $data;
    }

    public function getNomenklatorById(int $id) : ?Nomenklator
    {
        $query = "SELECT * FROM "
            . Nomenklator::$table_name . " WHERE id=:id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$id,PDO::PARAM_INT);
        $stm->execute();
        $data = $stm->fetchObject("Nomenklator");
        if($data instanceof Nomenklator)
        {
            $q = "SELECT url from ". NomenklatorImage::$tableName . " ORDER BY `order` ASC WHERE nomenklatorId=:nomenklatorId";
            $stm2 = $this->conn->prepare($q);
            $stm2->bindParam(':nomenklatorId',$id);
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

    public function createNomenklator(array $nomenklator): ?int
    {
        $query = "INSERT INTO " . Nomenklator::$table_name . " (`simple`, homophonic, bigrams, trigrams, codeBook,
         `nulls`, folder, structure) values (`:simple`, :homophonic, :bigrams, :trigrams, :codeBook, `:nulls`,
        :folder, :structure)";
        $stm = $this->conn->prepare($query);
        /*$stm->bindParam(":s`",$simple);
        $stm->bindParam(":homophonic",$homophonic);
        $stm->bindParam(":bigrams",$bigrams);
        $stm->bindParam(":trigrams",$trigrams);
        $stm->bindParam(":codeBook",$codeBook);
        $stm->bindParam(":n",$nulls);
        $stm->bindParam(":folder",$folder);
        $stm->bindParam(":structure",$structure);*/
        $this->conn->beginTransaction();
        try {
            var_dump( $nomenklator);
            $ans = $stm->execute($nomenklator);
            //echo var_dump($ans);
            if ($ans)
            {
                $id = $this->conn->lastInsertId();
                $i = 1;
                foreach ($nomenklator->images as $image)
                {
                    $q = "INSERT INTO " . NomenklatorImage::$tableName .
                        " (url,`order`,nomenklatorId) VALUES (:url,:ord,:nomenklatorId)";
                    $stm2 = $this->conn->prepare($q);
                    $stm2->bindParam(':url', $image["url"]);
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
        $query = "SELECT id,username, passwordHash from " . User::$tableName . " WHERE username=:username";
        $stm=$this->conn->prepare($query);
        $stm->bindParam(':username',$username);
        $stm->execute();
        $data = $stm->fetchObject("User");
        if($data instanceof User)
        {
            return $data->id;
            /*if(password_verify($password,$data->passwordHash))
            {
                return $data->id;
            }
            else
            {
                return null;
            }*/
        }
        return null;
    }

    public function getFolders(): ?array
    {
        $query = "SELECT `name`,fond FROM " . NomenklatorFolder::$tableName;
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $data = $stm->fetchAll(PDO::FETCH_CLASS,"NomenklatorFolder");
        if($data)
            return $data;
        return null;
    }

    public function addUser(string $username, string $password): ?int
    {
        $query = "SELECT 1 from " . User::$tableName . " WHERE username=:username";
        $stm=$this->conn->prepare($query);
        $stm->bindParam(':username',$username);
        $stm->execute();
        $data = $stm->fetch(PDO::FETCH_ASSOC);
        if(!$data)
        {
            $q = "INSERT INTO " . User::$tableName . "(username, passwordHash) VALUES (:username,:passwordHash)";
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

        $stmt = $this->conn->prepare("SELECT * FROM " . LoginInfo::$table_name . " WHERE selector = ?");
        $stmt->execute([$tokenLeft]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        //echo var_dump($row);
        if(hash_equals($row['hash'],$tokenRightHashed))
        {
            try {
                $expires_date = new DateTime($row['expiresAt']);
                $current_date = new DateTime('now');
                if($expires_date > $current_date)
                {
                    echo var_dump($row['userId']);
                    return intval($row['userId']);
                }
            } catch (Exception $e) {
                throw new AuthorizationException('Invalid authentication token 1');
            }
        }
        else throw new AuthorizationException('Invalid authentication token 2');
        return null;
    }

    public function createToken(int $user_id): ?string
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

            $stmt->execute([$user_id,$tokenLeft,$tokenRightHashed,$date->format("Y-m-d"),$expire_date->format("Y-m-d")]);

            return $tokenLeft.':'.$tokenRight;

        } catch (Exception $e) {
        }
        return null;
    }

}


