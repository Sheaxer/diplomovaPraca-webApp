<?php

require_once (__DIR__ ."/../NomenclatorImageService.php");
require_once ("NomenclatorImageServiceImpl.php");
require_once ("KeyUserServiceImpl.php");
require_once (__DIR__ . "/../../controllers/helpers.php");
class NomenclatorKeyServiceImpl implements NomenclatorKeyService
{
    private  $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function createNomenclatorKey(int $userId, NomenclatorKey $nomenclator): ?int
    {
        if($nomenclator->signature === null)
            $nomenclator->signature = generateRandomString(6);
        while($this->nomenclatorKeyExistsBySignature($nomenclator->signature))
        {
            $nomenclator->signature .= generateRandomString(1);
        }
        $query = "INSERT INTO nomenclatorkeys (folder, signature, completeStructure, uploadedBy, date, language) VALUES 
(:folder,:signature,:completeStructure,:uploadedBy,:date,:language)";

        $stm = $this->conn->prepare($query);
        $stm->bindParam(':folder',$nomenclator->folder);
        $stm->bindParam(':signature',$nomenclator->signature);
        $stm->bindParam(':completeStructure',$nomenclator->completeStructure);
        $stm->bindParam(':uploadedBy',$userId);
        $date = date("Y-m-d H:i:s");
        $stm->bindParam(':date', $date);
        $stm->bindParam(":language",$nomenclator->language);

        $imageService = new NomenclatorImageServiceImpl($this->conn);

        $this->conn->beginTransaction();
        $stm->execute();
        $addedId = intval($this->conn->lastInsertId());
        $i=1;
        if($nomenclator->images !== null) {
            foreach ($nomenclator->images as $image) {
                if ($image instanceof NomenclatorImage) {
                    $imageService->createNomenclatorImage($image, $addedId, $i);
                    $i++;
                }
            }
        }
        $keyUserService = new KeyUserServiceImpl($this->conn);
        if($nomenclator->keyUsers !== null)
        {
            foreach ($nomenclator->keyUsers as $user)
            {
                if($user instanceof KeyUser)
                {
                    $userId = null;
                    if($user->id !== null)
                    {
                        $u = $keyUserService->getKeyUserById($user->id);
                        $userId = $u->id;
                    }
                    else if($user->name !== null)
                    {
                        $u = $keyUserService->getKeyUserByName($user->name);
                        $userId = $u->id;
                    }


                    if($userId !== null)
                        $keyUserService->assignKeyUserToNomenclatorKey($userId,$addedId);
                }

            }
        }

        $this->conn->commit();
        return $addedId;
    }

    private function fillNomenclator(NomenclatorKey $nomenclatorKey): NomenclatorKey
    {
        $s = new NomenclatorImageServiceImpl($this->conn);
        $nomenclatorKey->images = $s->getNomenclatorImagesOfNomenclatorKey($nomenclatorKey->id);

        $k = new KeyUserServiceImpl($this->conn);
        $nomenclatorKey->keyUsers = $k->getKeyUsersByNomenclatorKeyId($nomenclatorKey->id);

        $d = new DigitalizedTranscriptionServiceImpl($this->conn);
        $nomenclatorKey->digitalizedTranscriptions = $d->getDigitalizedTranscriptionsOfNomenclator($nomenclatorKey->id);

        $u = new KeyUserServiceImpl($this->conn);
        $nomenclatorKey->keyUsers = $u->getKeyUsersByNomenclatorKeyId($nomenclatorKey->id);

        return $nomenclatorKey;

    }

    public function getNomenclatorKeyById(int $id): ?NomenclatorKey
    {
        $query = "SELECT * FROM nomenclatorkeys WHERE id=:id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$id);
        $stm->execute();
        $nomenclatorKey = $stm->fetchObject('NomenclatorKey');
        if($nomenclatorKey instanceof NomenclatorKey)
        {
           $nomenclatorKey = $this->fillNomenclator($nomenclatorKey);
            return $nomenclatorKey;
        }
        return null;

    }

    public function getNomenclatorKeyBySignature(string $signature): ?NomenclatorKey
    {
        $query = "SELECT * FROM nomenclatorkeys WHERE signature=:signature";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':signature',$signature);
        $stm->execute();
        $nomenclatorKey = $stm->fetchObject('NomenclatorKey');
        if($nomenclatorKey instanceof NomenclatorKey)
        {
            $nomenclatorKey = $this->fillNomenclator($nomenclatorKey);
            return $nomenclatorKey;
        }
        return null;
    }

    public function getNomenklatorKeysByAttributes(?array $folders = null, ?array $structures = null): ?array
    {
        $query = "SELECT * FROM nomenclatorkeys";
        $wasNullFolder = false;
        $folderParams = 0;
        $removedNullFolders = array();
        if($folders !== null)
        {
            foreach ($folders as $folder)
            {
                if ($folder === '')
                {
                    $wasNullFolder = true;
                }
                else
                {
                    array_push($removedNullFolders,$folder);
                    if($folderParams > 0)
                    {
                        $query .= ", :folder" . strval($folderParams);
                        $folderParams+= 1;
                    }
                    else
                    {
                        $query .= " WHERE (folder IN ( :folder" . strval($folderParams);
                        $folderParams+=1;
                    }


                }

            }

        }
        if ($folderParams > 0)
        {
            $query .= ")";
            if ($wasNullFolder)
            {
                $query .= " OR folder IS NULL";
            }
            $query .= ")";
        }
        else
        {
            if ($wasNullFolder)
            {
                $query .= " WHERE (folder is NULL)";
            }
        }
        $structureParameter = 0;
        if($structures !== null)
        {
            if($folderParams > 0)
                $query .= " AND ( completeStructure IN (";
            else
                $query .= " WHERE ( completeStructure IN (";
            foreach ($structures as $structure)
            {
                if($structureParameter === 0)
                {
                    $query.= " :structure0";
                }
                else
                    $query .= ", :structure" . strval($structureParameter);

                $structureParameter+= 1;
            }

            $query .= "))";

        }
        //var_dump($query);
        $stm = $this->conn->prepare($query);
        if(!empty($removedNullFolders))
        {
            for ($i =0; $i<$folderParams; $i++)
            {
                $stm->bindParam(":folder" . strval($i), $removedNullFolders[$i]);
            }

        }
        if($structures !== null)
        {
            for ($i = 0; $i < $structureParameter; $i++)
            {
                $stm->bindParam(":structure" . strval($i), $structures[$i]);
            }
        }
        $stm->execute();
        $nomenclatorKeys = $stm->fetchAll(PDO::FETCH_CLASS,'NomenclatorKey');
        if($nomenclatorKeys === false)
            return null;
        $keys = array();
        foreach ($nomenclatorKeys as $key)
        {
            array_push($keys,$this->fillNomenclator($key));
        }
        return $keys;
    }

    public function nomenclatorKeyExistsById($keyId) :bool
    {
        $query = "SELECT 1 FROM nomenclatorkeys where id=:id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$keyId);
        $stm->execute();
        $ans = $stm->fetchColumn(0);
        if($ans === false)
            return false;
        return true;
    }

    public function nomenclatorKeyExistsBySignature($signature): bool
    {
        $query = "SELECT 1 FROM nomenclatorkeys where signature=:signature";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(":signature",$signature);
        $stm->execute();
        $ans  = $stm->fetchColumn(0);
        if($ans === false)
            return false;
        return true;
    }
}