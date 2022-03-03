<?php

require_once (__DIR__ ."/../NomenclatorImageService.php");
require_once (__DIR__ ."/NomenclatorImageServiceImpl.php");
require_once (__DIR__ ."/KeyUserServiceImpl.php");
require_once (__DIR__ . "/../../controllers/helpers.php");
require_once (__DIR__ . "/../../entities/NomenclatorKeyState.php");
require_once (__DIR__ . "/NomenclatorPlaceServiceImpl.php");

class NomenclatorKeyServiceImpl implements NomenclatorKeyService
{
    private  $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function createNomenclatorKey(int $userId, NomenclatorKey $nomenclator): ?array
    {
        if($nomenclator->signature === null)
            $nomenclator->signature = generateRandomString(6);
        while($this->nomenclatorKeyExistsBySignature($nomenclator->signature))
        {
            $nomenclator->signature .= generateRandomString(1);
        }

        $stateQuery = "INSERT INTO nomenclatorKeyState (`state`, createdBy, createdAt, updatedAt, note) VALUES (:stateString, :createdBy, :createdAt, :updatedAt, :note)";
        $stateStm = $this->conn->prepare($stateQuery);
        $stateStm->bindValue(':stateString', NomenclatorKeyState::STATE_NEW);
        $now = new DateTime();
        $stateStm->bindParam(':createdBy', $userId);
        $stateStm->bindValue(':createdAt', $now->format('Y-m-d H:i:s'));
        $stateStm->bindValue(':updatedAt',  $now->format('Y-m-d H:i:s'));
        $stateStm->bindValue(':note', '');

        $this->conn->beginTransaction();
        
        $stateStm->execute();
        $stateId = intval($this->conn->lastInsertId());
        
        $query = "INSERT INTO nomenclatorKeys (folder, `signature`, completeStructure, `language`, 
            stateId, usedChars,  cipherType, keyType, usedFrom, usedTo, usedAround, 
            placeOfCreation, groupId) 
        VALUES 
            (:folder, :signatureStr, :completeStructure, :lang ,:stateId, :usedChars, :cipherType, :keyType, 
            :usedFrom, :usedTo, :usedAround, :placeOfCreation, :groupId)";

        $stm = $this->conn->prepare($query);
        $stm->bindParam(':folder',$nomenclator->folder);
        $stm->bindParam(':signatureStr',$nomenclator->signature);
        $stm->bindParam(':completeStructure',$nomenclator->completeStructure);
        //$date = date("Y-m-d H:i:s");
        //$stm->bindParam(':date', $date);
        $stm->bindParam(":lang",$nomenclator->language);
        $stm->bindParam(':stateId', $stateId);
        $stm->bindParam(':usedChars', $nomenclator->usedChars);
        $stm->bindParam(':cipherType', $nomenclator->cipherType);
        $stm->bindParam(':keyType', $nomenclator->keyType);
        $stm->bindValue(':usedFrom', $nomenclator->usedFrom ? $nomenclator->usedFrom->format('Y-m-d H:i:s') : null);
        $stm->bindValue(':usedTo', $nomenclator->usedTo ? $nomenclator->usedTo->format('Y-m-d H:i:s') : null);
        $stm->bindValue(':usedAround', $nomenclator->usedAround ? $nomenclator->useusedArounddTo->format('Y-m-d H:i:s') : null);
        $stm->bindParam(':placeOfCreation', $nomenclator->placeOfCreationId);
        $stm->bindParam(':groupId', $nomenclator->groupId);

        $imageService = new NomenclatorImageServiceImpl($this->conn);

        
        $a = $stm->execute();
        if (! $a) {
            $this->conn->rollBack();
            throw new Exception('Unable to create nomenclator key');
        }
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
                        $keyUserService->assignKeyUserToNomenclatorKey($userId, $addedId, $user->isMainUser);
                }

            }
        }

        $this->conn->commit();
        return [
            'id' => $addedId,
            'stateId' => $stateId
        ];
    }

    private function fillNomenclator(?array $userInfo, NomenclatorKey $nomenclatorKey): NomenclatorKey
    {
        $s = new NomenclatorImageServiceImpl($this->conn);
        $nomenclatorKey->images = $s->getNomenclatorImagesOfNomenclatorKey($nomenclatorKey->id);

        $k = new KeyUserServiceImpl($this->conn);
        $nomenclatorKey->keyUsers = $k->getKeyUsersByNomenclatorKeyId($nomenclatorKey->id);

        $d = new DigitalizedTranscriptionServiceImpl($this->conn);
        $nomenclatorKey->digitalizedTranscriptions = $d->getDigitalizedTranscriptionsOfNomenclator($userInfo, $nomenclatorKey->id);

        $p = new NomenclatorPlaceServiceImpl($this->conn);


        if ($nomenclatorKey->state && $nomenclatorKey->state->createdById) {
            $u = new SystemUserServiceImpl($this->conn);
            $nomenclatorKey->state->createdBy = $u->getUsernameById($nomenclatorKey->state->createdById);
        }
       
        
        /* TODO fill in folder and used where? */
        /*$u = new KeyUserServiceImpl($this->conn);
        $nomenclatorKey->keyUsers = $u->getKeyUsersByNomenclatorKeyId($nomenclatorKey->id);*/

        return $nomenclatorKey;

    }

    public function getNomenclatorKeyById(?array $userInfo, int $id): ?NomenclatorKey
    {
        $query = "SELECT k.*, s.state, s.createdBy, s.createdAt, s.updatedAt, s.note FROM nomenclatorKeys k INNER JOIN nomenclatorKeyState s ON k.stateId = s.id WHERE k.id=:id";
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $query .= " AND (s.createdBy = :createById OR s.state= :approvedState)";
            }
        } else {
            $query .= " AND (s.state=:approvedState)";
        }
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$id);
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $stm->bindParam(':createById', $userInfo['id']);
                $stm->bindValue(':approvedState' ,NomenclatorKeyState::STATE_APPROVED);
            }
        } else {
            $stm->bindValue(':approvedState' ,NomenclatorKeyState::STATE_APPROVED);
        }
        $stm->execute();
        $nomenclatorKeyData = $stm->fetch(PDO::FETCH_ASSOC);
       
        $nomenclatorKey = NomenclatorKey::createFromArray($nomenclatorKeyData);
        $nomenclatorKey = $this->fillNomenclator($userInfo, $nomenclatorKey);
        return $nomenclatorKey;
    }

    public function getNomenclatorKeyBySignature(?array $userInfo, string $signature): ?NomenclatorKey
    {
        $query = "SELECT k.*,  s.state, s.createdBy, s.createdAt, s.updatedAt, s.note FROM nomenclatorKeys k INNER JOIN nomenclatorKeyState s ON k.stateId = s.id WHERE signature=:signature";
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $query .= " AND (s.createdBy = :createById OR s.state= :approvedState)";
            }
        } else {
            $query .= " AND (s.state=:approvedState)";
        }
        
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':signature',$signature);
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $stm->bindParam(':createById', $userInfo['id']);
                $stm->bindValue(':approvedState' ,NomenclatorKeyState::STATE_APPROVED);
            }
        } else {
            $stm->bindValue(':approvedState' ,NomenclatorKeyState::STATE_APPROVED);
        }
        
        $stm->execute();
        $nomenclatorKeyData = $stm->fetch(PDO::FETCH_ASSOC);
       
        $nomenclatorKey = NomenclatorKey::createFromArray($nomenclatorKeyData);
        $nomenclatorKey = $this->fillNomenclator($userInfo, $nomenclatorKey);
        return $nomenclatorKey;
    }

    public function getNomenklatorKeysByAttributes(?array $userInfo, $limit, $page, ?array $folders = null, ?array $structures = null): ?array
    {
        $query = "SELECT k.*, s.state, s.createdBy, s.createdAt, s.updatedAt, s.note FROM nomenclatorKeys k INNER JOIN nomenclatorKeyState s ON k.stateId = s.id";
        $wasNullFolder = false;
        $folderParams = 0;
        $removedNullFolders = array();

        $isWhereAlready = false;

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
                        $query .= " WHERE (k.folder IN ( :folder" . strval($folderParams);
                        $isWhereAlready = true;
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
                $query .= " OR k.folder IS NULL";
            }
            $query .= ")";
        }
        else
        {
            if ($wasNullFolder)
            {
                $query .= " WHERE (k.folder is NULL)";
                $isWhereAlready = true;
            }
        }
        $structureParameter = 0;
        if($structures !== null)
        {
            $isWhereAlready = true;
            if($folderParams > 0)
                $query .= " AND ( k.completeStructure IN (";
            else
                $query .= " WHERE ( k.completeStructure IN (";
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

        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                if ($isWhereAlready) {
                    $query .= ' AND ((s.createdBy = :createdById)';
                } else {
                    $query .= ' WHERE ((s.createdBy = :createdById)';
                }
                $query .= ' OR ( s.state = :approvedState))';
            }
        } else {
            if ($isWhereAlready) {
                $query .= ' AND ( s.state = :approvedState)';
            } else {
                $query .= ' WHERE ( s.state = :approvedState)';
            }
        }

        $query .= " LIMIT :offset, :pageLimit";
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
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $stm->bindParam(":createdById", $userInfo['id']);
                $stm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
            }
        } else {
            $stm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        }
        $offset = ($page - 1) * $limit;
        $stm->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stm->bindParam(":pageLimit", $limit, PDO::PARAM_INT);
        
        $stm->execute();

        $nomenclatorKeysData = $stm->fetchAll(PDO::FETCH_ASSOC);
        if($nomenclatorKeysData === false)
            return null;
        $keys = array();
        foreach ($nomenclatorKeysData as $nomenclatorKeyData)
        {
            $nomenclatorKey = NomenclatorKey::createFromArray($nomenclatorKeyData);
            //$nomKey = NomenclatorKey::createFromArray($key);
            array_push($keys,$this->fillNomenclator($userInfo, $nomenclatorKey));
        }
        return $keys;
    }

    public function nomenclatorKeyExistsById($keyId) :bool
    {
        $query = "SELECT 1 FROM nomenclatorKeys where id=:id";
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
        $query = "SELECT 1 FROM nomenclatorKeys where signature=:signature";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(":signature",$signature);
        $stm->execute();
        $ans  = $stm->fetchColumn(0);
        if($ans === false)
            return false;
        return true;
    }

    public function updateNomenclatorKeyState(array $userInfo,  $state, $note, ?int $nomenclatorId, ?int $stateId): bool
    {
        if (! $userInfo || ! isset($userInfo['isAdmin']) || ! $userInfo['isAdmin'])
            return false;
        $nomeclatorStateId = null;
        if ($nomenclatorId) {
            $query = "SELECT s.id FROM nomenclatorKeys k INNER JOIN nomenclatorKeyState s ON k.stateId = s.id WHERE k.id = :nomeclatorKeyId";
            $stm = $this->conn->prepare($query);
            $stm->bindParam(':nomeclatorKeyId', $nomenclatorId, PDO::PARAM_INT);
            $stm->execute();
            $nomeclatorStateId = $stm->fetch(PDO::FETCH_COLUMN); 
            if ($nomeclatorStateId) {
                $stateId = $nomeclatorStateId;
            }
        }
        if ($stateId) {
            $query2 = "UPDATE nomenclatorKeyState SET `state`=:stateString, note= :note, updatedAt = :updatedAt";
            $stm2 = $this->conn->prepare($query2);
            $stm2->bindParam(':stateString', $state);
            $stm2->bindParam(':note', $note);
            $stm2->bindValue(':updatedAt', (new DateTime())->format('Y-m-d H:i:s'));
            $res = $stm2->execute();
            return $res;

        }
        return false;

    }

    public function getNomenclatorKeyState(?array $userInfo, $nomenclatorId, $stateId): ?NomenclatorKeyState
    {
        $query = "SELECT s.* FROM nomenclatorKeys k INNER JOIN nomenclatorKeyState ON k.stateId = s.id WHERE ";
        if ($nomenclatorId) {
            $query .= "k.id = :nomenclatorKeyId";
        } else if ($stateId) {
            $query .= " s.id = :stateId";
        } else {
            return null;
        }
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $query .= " AND (s.createdBy = :createdBy OR s.state = :approvedState)";
            }
        } else {
            $query .= " AND s.state = :approvedState";
        }

        $stm = $this->conn->prepare($query);

        if ($nomenclatorId) {
            $stm->bindParam(':nomenclatorKeyId', $nomenclatorId);
        } else {
            $stm->bindParam(':stateId', $stateId);
        }

        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $stm->bindParam(':createdBy', $userInfo['id']);
                $stm->bindValue(':approvedState', NomenclatorKeyState::STATE_APPROVED);
            }
        } else {
            $stm->bindValue(':approvedState', NomenclatorKeyState::STATE_APPROVED);
        }

        $stm->execute();

        return $stm->fetchObject('NomenclatorKeyState');
    }
}