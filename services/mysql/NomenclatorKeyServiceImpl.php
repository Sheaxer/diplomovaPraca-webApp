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

        $stateQuery = "INSERT INTO nomenclatorkeystate (`state`, createdBy, createdAt, updatedAt, note) VALUES (:stateString, :createdBy, :createdAt, :updatedAt, :note)";
        $stateStm = $this->conn->prepare($stateQuery);
        $stateStm->bindValue(':stateString', NomenclatorKeyState::STATE_AWAITING);
        $now = new DateTime();
        $stateStm->bindParam(':createdBy', $userId);
        $stateStm->bindValue(':createdAt', $now->format('Y-m-d H:i:s'));
        $stateStm->bindValue(':updatedAt',  $now->format('Y-m-d H:i:s'));
        $stateStm->bindValue(':note', '');

        $this->conn->beginTransaction();
        
        $wasSuccessful = $stateStm->execute();
        $stateId = intval($this->conn->lastInsertId());

        if (! $wasSuccessful || !$stateId) {
            $response = [
                'exception' => $this->conn->errorInfo()[2]
            ];
            $this->conn->rollBack();
            return $response;
        }
        
        $query = "INSERT INTO nomenclatorkeys (folder, `signature`, completeStructure, `language`, 
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
        $stm->bindParam(':stateId', $stateId, PDO::PARAM_INT);
        $stm->bindParam(':usedChars', $nomenclator->usedChars);
        $stm->bindParam(':cipherType', $nomenclator->cipherType);
        $stm->bindParam(':keyType', $nomenclator->keyType);
        $stm->bindValue(':usedFrom', $nomenclator->usedFrom ? $nomenclator->usedFrom->format('Y-m-d H:i:s') : null);
        $stm->bindValue(':usedTo', $nomenclator->usedTo ? $nomenclator->usedTo->format('Y-m-d H:i:s') : null);
        $stm->bindParam(':usedAround', $nomenclator->usedAround);
        $stm->bindParam(':placeOfCreation', $nomenclator->placeOfCreationId, PDO::PARAM_INT);
        if ($nomenclator->groupId) {
            $stm->bindParam(':groupId', $nomenclator->groupId, PDO::PARAM_INT);
        } else {
            $maxGroupIdQuery = 'SELECT groupId FROM nomenclatorkeys ORDER BY groupId DESC LIMIT 1';
            $maxGroupIdStm = $this->conn->prepare($maxGroupIdQuery);
            $maxGroupIdStm->execute();
            $maxGroupId = $maxGroupIdStm->fetchColumn(0);
            if (! $maxGroupId) {
                $maxGroupId = 1;
            } else {
                $maxGroupId++;
            }
            $stm->bindParam(':groupId', $maxGroupId, PDO::PARAM_INT);
        }
        //

        $imageService = new NomenclatorImageServiceImpl($this->conn);
        
        $a = $stm->execute();
        if (! $a) {
            
            $err = $this->conn->errorInfo();
            $this->conn->rollBack();
            throw new Exception('Unable to create nomenclator key ' . $err[2]);
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
                        if ($u) {
                            $userId = $u->id;
                        } else {
                            $userId = $keyUserService->createKeyUser($user, false);
                        }
                    }
                    if($userId !== null)
                        $keyUserService->assignKeyUserToNomenclatorKey($userId, $addedId, $user->isMainUser);
                }

            }
        }

        $this->conn->commit();
        return [
            'id' => $addedId,
            'stateId' => intval($stateId)
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

        if ($nomenclatorKey->placeOfCreationId) {
            $p = new NomenclatorPlaceServiceImpl($this->conn);
            $nomenclatorKey->placeOfCreation =  $p->getPlaceById(intval($nomenclatorKey->placeOfCreationId));
        }
       
        if ($nomenclatorKey->state && $nomenclatorKey->state->createdById) {
            $u = new SystemUserServiceImpl($this->conn);
            $nomenclatorKey->state->createdBy = $u->getUsernameById($nomenclatorKey->state->createdById);
        }
        if ($nomenclatorKey->folder) {
            $f = new NomenclatorFolderServiceImpl($this->conn);
            $fold = $f->getFolderByName($nomenclatorKey->folder);
            $nomenclatorKey->folder = $fold;
        }
       
        /* TODO fill in folder and used where? */
        /*$u = new KeyUserServiceImpl($this->conn);
        $nomenclatorKey->keyUsers = $u->getKeyUsersByNomenclatorKeyId($nomenclatorKey->id);*/
        unset($nomenclatorKey->placeOfCreationId);
        return $nomenclatorKey;

    }

    public function getNomenclatorKeyById(?array $userInfo, int $id): ?NomenclatorKey
    {
        $query = "SELECT k.*, s.state, s.createdBy, s.createdAt, s.updatedAt, s.note FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE k.id=:id";
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
        if (! $nomenclatorKeyData) {
            return null;
        }
        $nomenclatorKey = NomenclatorKey::createFromArray($nomenclatorKeyData);
        $nomenclatorKey = $this->fillNomenclator($userInfo, $nomenclatorKey);
        return $nomenclatorKey;
    }

    public function getNomenclatorKeyBySignature(?array $userInfo, string $signature): ?NomenclatorKey
    {
        $query = "SELECT k.*,  s.state, s.createdBy, s.createdAt, s.updatedAt, s.note FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE signature=:signature";
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

    public function getNomenklatorKeysByAttributes(?array $userInfo, $limit, $page, 
        ?array $folders = null, ?array $structures = null,
        bool $myKeys = false, ?string $state = null, ?int $createdBy = null
    ): ?array
    {
        $selectQuery = "SELECT k.*, s.state, s.createdBy, s.createdAt, s.updatedAt, s.note ";
        $countQuery  ="SELECT COUNT(k.id) ";
        $query = "FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id";
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
                    $isWhereAlready = true;
                }
                $query .= ' OR ( s.state = :approvedState))';
            }
        } else {
            if ($isWhereAlready) {
                $query .= ' AND ( s.state = :approvedState)';
            } else {
                $query .= ' WHERE ( s.state = :approvedState)';
                $isWhereAlready = true;
            }
        }

        if ($userInfo && $myKeys) {
            if ($isWhereAlready) {
                $query .= " AND (s.createdBy = :myId)";
            } else {
                $query .= " WHERE (s.createdBy = :myId)";
                $isWhereAlready = true;
            }
        }

        if ($state) {
            if ($isWhereAlready) {
                $query .= " AND (s.`state` = :currentState)";
            } else {
                $query .= " WHERE (s.`state` = :currentState)";
                $isWhereAlready = true;
            }
        }
        if ($createdBy) {
            if ($isWhereAlready) {
                $query .= " AND (s.createdBy = :keyCreatedById)";
            } else {
                $query .= " WHERE (s.createdBy = :keyCreatedById)";
                $isWhereAlready = true;
            }
        }

        $countQuery.= $query;
        $query =  $selectQuery . $query;
        if ($limit) {
            $query .= " LIMIT :offset, :pageLimit";
        }
       
        //var_dump($query);
        $stm = $this->conn->prepare($query);
        $countStm = $this->conn->prepare($countQuery);
        if(!empty($removedNullFolders))
        {
            for ($i =0; $i<$folderParams; $i++)
            {
                $stm->bindParam(":folder" . strval($i), $removedNullFolders[$i]);
                $countStm->bindParam(":folder" . strval($i), $removedNullFolders[$i]);
            }

        }
        if($structures !== null)
        {
            for ($i = 0; $i < $structureParameter; $i++)
            {
                $stm->bindParam(":structure" . strval($i), $structures[$i]);
                $countStm->bindParam(":structure" . strval($i), $structures[$i]);
            }
        }
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $stm->bindParam(":createdById", $userInfo['id']);
                $stm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
                $countStm->bindParam(":createdById", $userInfo['id']);
                $countStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
            }
        } else {
            $stm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
            $countStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        }
        if ($limit) {
            $offset = ($page - 1) * $limit;
            $stm->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stm->bindParam(":pageLimit", $limit, PDO::PARAM_INT);
        }

        if ($userInfo && $myKeys) {
            $stm->bindParam(":myId", $userInfo['id'], PDO::PARAM_INT);
            $countStm->bindParam(":myId", $userInfo['id'], PDO::PARAM_INT);
        }
        if ($state) {
            $stm->bindParam(":currentState", $state);
            $countStm->bindParam(":currentState", $state );
        }
        if ($createdBy) {
            $stm->bindParam(":keyCreatedById", $createdBy, PDO::PARAM_INT);
            $countStm->bindParam(":keyCreatedById", $createdBy, PDO::PARAM_INT );
        }
        
        $stm->execute();
        $countStm->execute();
        $nomenclatorKeysData = $stm->fetchAll(PDO::FETCH_ASSOC);
        $count = intval($countStm->fetchColumn(0));
        
        if($nomenclatorKeysData === false)
            return null;
        $keys = array();
        foreach ($nomenclatorKeysData as $nomenclatorKeyData)
        {
            $nomenclatorKey = NomenclatorKey::createFromArray($nomenclatorKeyData);
            //$nomKey = NomenclatorKey::createFromArray($key);
            array_push($keys,$this->fillNomenclator($userInfo, $nomenclatorKey));
        }
        $isNextPage = false;
        if ($limit) {
            $end = $offset + $limit;
            if ($end < $count) {
                $isNextPage = true;
            }
        }
       
        return [
            'count' => $count,
            'nextPage' => $isNextPage,
            'items' => $keys,
        ];
    }

    public function nomenclatorKeyExistsById(?array $userInfo, $keyId) :bool
    {
        $query = "SELECT 1 FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id where k.id=:id";
        
        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $query .= " AND (s.createdBy = :createById OR s.state= :approvedState)";
            }
        } else {
            $query .= " AND (s.state=:approvedState)";
        }
        
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$keyId);

        if ($userInfo) {
            if (! $userInfo['isAdmin']) {
                $stm->bindParam(':createById', $userInfo['id']);
                $stm->bindValue(':approvedState' ,NomenclatorKeyState::STATE_APPROVED);
            }
        } else {
            $stm->bindValue(':approvedState' ,NomenclatorKeyState::STATE_APPROVED);
        }

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

    public function updateNomenclatorKeyState(array $userInfo,  $state, $note, ?int $nomenclatorId, ?int $stateId): bool
    {
        if (! $userInfo || ! isset($userInfo['isAdmin']) || ! $userInfo['isAdmin'])
            return false;
        $nomeclatorStateId = null;
        if ($nomenclatorId) {
            $query = "SELECT s.id FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE k.id = :nomeclatorKeyId";
            $stm = $this->conn->prepare($query);
            $stm->bindParam(':nomeclatorKeyId', $nomenclatorId, PDO::PARAM_INT);
            $stm->execute();
            $nomeclatorStateId = $stm->fetch(PDO::FETCH_COLUMN); 
            if ($nomeclatorStateId) {
                $stateId = $nomeclatorStateId;
            }
        }
        if ($stateId) {
            $query2 = "UPDATE nomenclatorkeystate SET `state`=:stateString, note= :note, updatedAt = :updatedAt where id=:stateId";
            $stm2 = $this->conn->prepare($query2);
            $stm2->bindParam(':stateString', $state);
            $stm2->bindParam(':note', $note);
            $stm2->bindValue(':updatedAt', (new DateTime())->format('Y-m-d H:i:s'));
            $stm2->bindParam(':stateId', $stateId, PDO::PARAM_INT);
            $res = $stm2->execute();
            return $res;

        }
        return false;

    }

    public function getNomenclatorKeyState(?array $userInfo, $nomenclatorId, $stateId): ?NomenclatorKeyState
    {
        $query = "SELECT s.* FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate ON k.stateId = s.id WHERE ";
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

    public function updateNomenclatorKey(array $userInfo, NomenclatorKey $nomenclator)
    {
        $this->conn->beginTransaction();
        $getStateId = "SELECT stateId FROM nomenclatorkeys WHERE id=:id";
        $getStateIdStm = $this->conn->prepare($getStateId);
        $getStateIdStm->setFetchMode(PDO::FETCH_ASSOC);
        $getStateIdStm->bindParam(":id", $nomenclator->id, PDO::PARAM_INT);
        $getStateIdStm->execute();
        $stateId = $getStateIdStm->fetchColumn(0);
        if (! $stateId) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        $getStateQuery = 'SELECT * from nomenclatorkeystate WHERE id=:stateId';
        $getStateStm = $this->conn->prepare($getStateQuery);
        $getStateStm->bindParam(":stateId", $stateId, PDO::PARAM_INT);
        $getStateStm->setFetchMode(PDO::FETCH_CLASS, NomenclatorKeyState::class);
        $getStateStm->execute();
        $state = $getStateStm->fetch();
        if (! $state) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }
        /** @var NomenclatorKeyState $state */
        if ($state->createdBy != $userInfo['id']) {
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => 'User cannot update keys not created by them',
            ];
        }
        if ($state->state != NomenclatorKeyState::STATE_APPROVED && $state->state != NomenclatorKeyState::STATE_AWAITING) {
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => 'Only keys in state awaiting or approved can be updated',
            ];
        }


        $query = "UPDATE nomenclatorkeys SET folder = :folder, `signature` = :signatureStr, 
        completeStructure = :completeStructure, `language` = :lang, 
            usedChars = :usedChars,  cipherType = :cipherType, keyType = :keyType, 
            usedFrom = :usedFrom, usedTo = :usedTo, usedAround = :usedAround , 
            placeOfCreation = :placeOfCreation, groupId = :groupId 
            WHERE id = :id";
        
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':folder',$nomenclator->folder);
        $stm->bindParam(':signatureStr',$nomenclator->signature);
        $stm->bindParam(':completeStructure',$nomenclator->completeStructure);
        //$date = date("Y-m-d H:i:s");
        //$stm->bindParam(':date', $date);
        $stm->bindParam(":lang",$nomenclator->language);
        $stm->bindParam(':usedChars', $nomenclator->usedChars);
        $stm->bindParam(':cipherType', $nomenclator->cipherType);
        $stm->bindParam(':keyType', $nomenclator->keyType);
        $stm->bindValue(':usedFrom', $nomenclator->usedFrom ? $nomenclator->usedFrom->format('Y-m-d H:i:s') : null);
        $stm->bindValue(':usedTo', $nomenclator->usedTo ? $nomenclator->usedTo->format('Y-m-d H:i:s') : null);
        $stm->bindParam(':usedAround', $nomenclator->usedAround);
        $stm->bindParam(':placeOfCreation', $nomenclator->placeOfCreationId, PDO::PARAM_INT);
        $stm->bindParam(':groupId', $nomenclator->groupId, PDO::PARAM_INT);
        $stm->bindParam(':id', $nomenclator->id, PDO::PARAM_INT);

        $result = $stm->execute();
        if (! $result) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        


        $updateQuery = "UPDATE nomenclatorkeystate SET `state`=:stateString, updatedAt = :updatedAt WHERE id = :stateId";
        $updateStm = $this->conn->prepare($updateQuery);
        $updateStm->bindValue(":stateString", NomenclatorKeyState::STATE_AWAITING);
        $now  = new DateTime();
        $updateStm->bindValue(':updatedAt', $now->format("Y-m-d H:i:s"));
        $updateStm->bindParam(":stateId", $stateId, PDO::PARAM_INT);
        $wasExecuted = $updateStm->execute();
        if (! $wasExecuted) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        $this->conn->commit();
        return [
            'status' => 'success',
        ];
    }

    public function addKeyUsersToNomenclatorKey(array $userInfo, int $nomenclatorKeyId, array $users)
    {
        $this->conn->beginTransaction();
        $getStateId = "SELECT stateId FROM nomenclatorkeys WHERE id=:id";
        $getStateIdStm = $this->conn->prepare($getStateId);
        $getStateIdStm->setFetchMode(PDO::FETCH_ASSOC);
        $getStateIdStm->bindParam(":id", $nomenclatorKeyId, PDO::PARAM_INT);
        $getStateIdStm->execute();
        $stateId = $getStateIdStm->fetchColumn(0);
        if (! $stateId) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        $getStateQuery = 'SELECT * from nomenclatorkeystate WHERE id=:stateId';
        $getStateStm = $this->conn->prepare($getStateQuery);
        $getStateStm->bindParam(":stateId", $stateId, PDO::PARAM_INT);
        $getStateStm->setFetchMode(PDO::FETCH_CLASS, NomenclatorKeyState::class);
        $getStateStm->execute();
        $state = $getStateStm->fetch();
        if (! $state) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }
        /** @var NomenclatorKeyState $state */
        if ($state->createdBy != $userInfo['id']) {
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => 'User cannot update keys not created by them',
            ];
        }
        if ($state->state != NomenclatorKeyState::STATE_APPROVED && $state->state != NomenclatorKeyState::STATE_AWAITING) {
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => 'Only keys in state awaiting or approved can be updated',
            ];
        }

        $keyUserService = new KeyUserServiceImpl($this->conn);
        //$this->conn->beginTransaction();
        /** @var KeyUser $user */
        foreach ($users as $user) {
            $userId = null;
            if ($user->id) {
                $u = $keyUserService->getKeyUserById($user->id);
                $userId = $user->id;
                if (! $u) {
                    $this->conn->rollBack();
                    return [
                        'status' => 'error',
                        'error' => 'Unknown key user'
                    ];
                }
            } else if ($user->name) {
                $u = $keyUserService->getKeyUserByName($user->name);
                if ($u) {
                    $userId = $u->id;
                } else {
                    $userId = $keyUserService->createKeyUser($user, false);
                }
            } else {
                $this->conn->rollBack();
                return [
                    'status' => 'error',
                    'error' => 'Unknown key user'
                ];
            }

            $keyUserService->assignKeyUserToNomenclatorKey($userId, $nomenclatorKeyId, $user->isMainUser);
        }

        $updateQuery = "UPDATE nomenclatorkeystate SET `state`=:stateString, updatedAt = :updatedAt WHERE id = :stateId";
        $updateStm = $this->conn->prepare($updateQuery);
        $updateStm->bindValue(":stateString", NomenclatorKeyState::STATE_AWAITING);
        $now  = new DateTime();
        $updateStm->bindValue(':updatedAt', $now->format("Y-m-d H:i:s"));
        $updateStm->bindParam(":stateId", $stateId, PDO::PARAM_INT);
        $wasExecuted = $updateStm->execute();
        if (! $wasExecuted) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        $this->conn->commit();
        return [
            'status' => 'success',
        ];
    }

    public function removeKeyUsersFromNomenclatorKey(array $userInfo, int $nomenclatorKeyId, array $users)
    {
        $this->conn->beginTransaction();
        $getStateId = "SELECT stateId FROM nomenclatorkeys WHERE id=:id";
        $getStateIdStm = $this->conn->prepare($getStateId);
        $getStateIdStm->setFetchMode(PDO::FETCH_ASSOC);
        $getStateIdStm->bindParam(":id", $nomenclatorKeyId, PDO::PARAM_INT);
        $getStateIdStm->execute();
        $stateId = $getStateIdStm->fetchColumn(0);
        if (! $stateId) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        $getStateQuery = 'SELECT * from nomenclatorkeystate WHERE id=:stateId';
        $getStateStm = $this->conn->prepare($getStateQuery);
        $getStateStm->bindParam(":stateId", $stateId, PDO::PARAM_INT);
        $getStateStm->setFetchMode(PDO::FETCH_CLASS, NomenclatorKeyState::class);
        $getStateStm->execute();
        $state = $getStateStm->fetch();
        if (! $state) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }
        /** @var NomenclatorKeyState $state */
        if ($state->createdBy != $userInfo['id']) {
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => 'User cannot update keys not created by them',
            ];
        }
        if ($state->state != NomenclatorKeyState::STATE_APPROVED && $state->state != NomenclatorKeyState::STATE_AWAITING) {
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => 'Only keys in state awaiting or approved can be updated',
            ];
        }

        $keyUserService = new KeyUserServiceImpl($this->conn);
       
        /** @var KeyUser $user */
        foreach ($users as $user) {
            $userId = null;
            if ($user->id) {
                $u = $keyUserService->getKeyUserById($user->id);
                $userId = $user->id;
                if (! $u) {
                    $this->conn->rollBack();
                    return [
                        'status' => 'error',
                        'error' => 'Unknown key user'
                    ];
                }
            } else if ($user->name) {
                $u = $keyUserService->getKeyUserByName($user->name);
                if ($u) {
                    $userId = $u->id;
                } else {
                    $this->conn->rollBack();
                    return [
                        'status' => 'error',
                        'error' => 'Unknown key user'
                    ];
                }
            } else {
                $this->conn->rollBack();
                return [
                    'status' => 'error',
                    'error' => 'Unknown key user'
                ];
            }

            $result = $keyUserService->removeKeyUserFromNomenclatorKey($nomenclatorKeyId, $userId, false);
            if ($result['status'] == 'error') {
                $this->conn->rollBack();
                return $result;
            }
        }

        $updateQuery = "UPDATE nomenclatorkeystate SET `state`=:stateString, updatedAt = :updatedAt WHERE id = :stateId";
        $updateStm = $this->conn->prepare($updateQuery);
        $updateStm->bindValue(":stateString", NomenclatorKeyState::STATE_AWAITING);
        $now  = new DateTime();
        $updateStm->bindValue(':updatedAt', $now->format("Y-m-d H:i:s"));
        $updateStm->bindParam(":stateId", $stateId, PDO::PARAM_INT);
        $wasExecuted = $updateStm->execute();
        if (! $wasExecuted) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode,
            ];
        }

        $this->conn->commit();
        return [
            'status' => 'success',
        ];
    }

}