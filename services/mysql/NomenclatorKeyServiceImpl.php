<?php

require_once (__DIR__ ."/../NomenclatorImageService.php");
require_once ("NomenclatorImageServiceImpl.php");
require_once ("KeyUserServiceImpl.php");
require_once (__DIR__ . "/../../controllers/helpers.php");
class NomenclatorKeyServiceImpl implements NomenclatorKeyService
{
    private PDO $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function createNomenclatorKey(int $userId, NomenclatorKey $nomenclator): ?int
    {

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
        if($folders !== null)
        {
            // TODO: add things
        }
        if($structures !== null)
        {
            // TODO: implement filtration
        }
        $stm = $this->conn->prepare($query);
        if($folders !== null)
        {
            // TODO: add things
        }
        if($structures !== null)
        {
            // TODO: implement filtration
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