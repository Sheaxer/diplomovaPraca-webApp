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

    public function createKeyUser(KeyUser $user): ?int
    {
        $query = "INSERT INTO keyusers (name) VALUES (:name)";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':name',$user->name);
        $this->conn->beginTransaction();
        $stm->execute();
        $id = intval($this->conn->lastInsertId());
        $this->conn->commit();
        return $id;
    }

    public function assignKeyUserToNomenclatorKey(int $id, int $nomenclatorKeyId, $isMainUser)
    {
        $query = "INSERT INTO nomenclatorkeyusers (userId, nomenclatorKeyId, isMainUser) VALUES (:userId, :nomenclatorKeyId, :isMainUser)";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':userId',$id, PDO::PARAM_INT);
        $stm->bindParam(':nomenclatorKeyId',$nomenclatorKeyId, PDO::PARAM_INT);
        $stm->bindParam(':isMainUser', $isMainUser, PDO::PARAM_BOOL);
        $stm->execute();
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
        $q = "SELECT * from keyusers LIMIT :offset, :pageLimit";
        $countQuery = "SELECT COUNT(*) from keyusers";
        $stm = $this->conn->prepare($q);
        $countStm = $this->conn->prepare($countQuery);
        $countStm->execute();
        $count = intval($countStm->fetchColumn(0));
        $offset = ($page-1)*$limit;
        $stm->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stm->bindParam(':pageLimit', $limit, PDO::PARAM_INT);
        $stm->execute();
        $res = $stm->fetchAll(PDO::FETCH_CLASS,"KeyUser");
        $isNextPage = false;
        if (($offset + $limit) < $count) {
            $isNextPage = true;
        }
        return [
            'count'    => $count,
            'nextPage' => $isNextPage,
            'items'    => $res,
        ];
    }
}