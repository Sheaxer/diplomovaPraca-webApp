<?php

require_once (__DIR__ . "/../KeyUserService.php");
require_once (__DIR__ . "/../../entities/NomenclatorKey.php");
require_once (__DIR__ . "/../../entities/KeyUser.php");

class KeyUserServiceImpl implements KeyUserService
{

    private  $conn;

    function __construct(PDO $PDO)
    {
        $this->conn = $PDO;
    }

    public function createKeyUser(KeyUser $user, $doTransaction = true): ?int
    {
        $query = "INSERT INTO keyusers (name) VALUES (:name)";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':name',$user->name);
        if ($doTransaction) {
            $this->conn->beginTransaction();
        }
        $stm->execute();
        $id = intval($this->conn->lastInsertId());
        if ($doTransaction) {
            $this->conn->commit();
        }
      
        return $id;
    }

    public function assignKeyUserToNomenclatorKey(int $id, int $nomenclatorKeyId, $isMainUser)
    {

        $existsQuery = "SELECT * from nomenclatorkeyusers where userId = :userId AND nomenclatorKeyId = :nomenclatorKeyId";
        $existsStm = $this->conn->prepare($existsQuery);
        $existsStm->bindParam(':userId',$id, PDO::PARAM_INT);
        $existsStm->bindParam(':nomenclatorKeyId',$nomenclatorKeyId, PDO::PARAM_INT);
        $existsStm->execute();
        $res = $existsStm->fetchAll(PDO::FETCH_ASSOC);
        if ($res && ! empty($res)) {
            $updateQuery = "UPDATE nomenclatorkeyusers SET isMainUser = :isMainUser where userId = :userId AND nomenclatorKeyId = :nomenclatorKeyId";
            $updateStm = $this->conn->prepare($updateQuery);
            $updateStm->bindParam(':userId',$id, PDO::PARAM_INT);
            $updateStm->bindParam(':nomenclatorKeyId',$nomenclatorKeyId, PDO::PARAM_INT);
            $updateStm->bindParam(':isMainUser', $isMainUser, PDO::PARAM_BOOL);
            $updateStm->execute();
        } else {
            $query = "INSERT INTO nomenclatorkeyusers (userId, nomenclatorKeyId, isMainUser) VALUES (:userId, :nomenclatorKeyId, :isMainUser)";
            $stm = $this->conn->prepare($query);
            $stm->bindParam(':userId',$id, PDO::PARAM_INT);
            $stm->bindParam(':nomenclatorKeyId',$nomenclatorKeyId, PDO::PARAM_INT);
            $stm->bindParam(':isMainUser', $isMainUser, PDO::PARAM_BOOL);
            $stm->execute();
        }

       
    }

    public function getKeyUserByName(string $name): ?KeyUser
    {
        $query = "SELECT * FROM keyusers WHERE name=:name";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':name',$name);
        $stm->execute();
        $user=$stm->fetchObject("KeyUser");
        if($user === false)
            return null;
        return $user;
    }

    public function getKeyUserById(int $id): ?KeyUser
    {
        $q = "SELECT * from keyusers WHERE id=:id";
        $stm = $this->conn->prepare($q);
        $stm->bindParam(':id',$id);
        $stm->execute();
        $r = $stm->fetchObject("KeyUser");
        if($r === false)
            return null;
        return $r;
    }

    public function getKeyUsersByNomenclatorKeyId(int $id): ?array
    {
        $q = "SELECT userId, isMainUser FROM nomenclatorkeyusers WHERE nomenclatorKeyId=:nomenclatorKeyId";
        $stm = $this->conn->prepare($q);
        $stm->bindParam(':nomenclatorKeyId',$id);
        $stm->execute();
        $r = $stm->fetchAll(PDO::FETCH_ASSOC);
        if($r === false)
            return null;

        $users = [];
        foreach ($r as $i)
        {
            $tmp = $this->getKeyUserById($i['userId']);
            if($tmp !== null) {
                $tmp->isMainUser = $i['isMainUser'];
                array_push($users,$tmp);
            }
            
        }
        return $users;
    }

    public function getAllKeyUsers($page, $limit): ?array
    {
        $q = "SELECT * from keyusers";
        $countQuery = "SELECT COUNT(*) from keyusers";
        if ($limit) {
            $q .= " LIMIT :offset, :pageLimit";
        }
        $stm = $this->conn->prepare($q);
        $countStm = $this->conn->prepare($countQuery);
        $countStm->execute();
        $count = intval($countStm->fetchColumn(0));
        if ($limit) {
            $offset = ($page-1)*$limit;
            $stm->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stm->bindParam(':pageLimit', $limit, PDO::PARAM_INT);
        }
       
        $stm->execute();
        $res = $stm->fetchAll(PDO::FETCH_CLASS,"KeyUser");
       
        $isNextPage = false;
        if ($limit) {
            if (($offset + $limit) < $count) {
                $isNextPage = true;
            }
        }
        return [
            'count'    => $count,
            'nextPage' => $isNextPage,
            'items'    => $res,
        ];
    }

    public function removeKeyUserFromNomenclatorKey(int $nomenclatorKeyId, int $userId, bool $doTransaction = true)
    {
        $query = 'DELETE FROM nomenclatorkeyusers where nomenclatorKeyId =:nomenclatorKeyId AND userId=:userId';
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':nomenclatorKeyId', $nomenclatorKeyId, PDO::PARAM_INT);
        $stm->bindParam(':userId', $userId, PDO::PARAM_INT);
        if ($doTransaction) {
            $this->conn->beginTransaction();
        }
        $result = $stm->execute();
        if ($result === false) {
            $errorInfo = $this->conn->errorInfo()[2];
            if ($doTransaction) {
                $this->conn->rollBack();
            }
            return [
                'status' => 'error',
                'error' => $errorInfo,
            ];
        }
        if ($doTransaction) {
            $this->conn->commit();
        }
        return [
            'status' => 'success',
        ];
    }
}