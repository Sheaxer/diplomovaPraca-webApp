<?php

require_once ("entities/SystemUser.php");
require_once (__DIR__ ."/../SystemUserService.php");

require_once (__DIR__ . "/../../entities/LoginInfo.php");

class SystemUserServiceImpl implements SystemUserService
{
    private $conn;
    function __construct(PDO $PDO)
    {
        $this->conn = $PDO;
    }

    public function createSystemUser(string $userName, string $password, bool $isAdmin): int
    {
        $query = "INSERT INTO systemusers (username, passwordHash, `isAdmin`) VALUES (:username,:passwordHash, :isAdmin)";
        $stm = $this->conn->prepare($query);

        $stm->bindParam(':username',$userName);
        $password_hash = password_hash($password,PASSWORD_DEFAULT);
        $stm->bindParam(':passwordHash', $password_hash);
        $stm->bindParam(':isAdmin', $isAdmin);
        $this->conn->beginTransaction();
        $stm->execute();
        $addedId = intval($this->conn->lastInsertId());
        $this->conn->commit();
        return $addedId;
    }

    public function logIn(string $userName, string $password): ?array
    {
        $query= "SELECT id,passwordHash FROM systemusers WHERE username=:username";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':username',$userName);

        $stm->execute();

        $res = $stm->fetchObject('SystemUser');
        if($res instanceof SystemUser)
        {
            if(password_verify($password,$res->passwordHash))
                return [
                    'id' => $res->id,
                    'isAdmin' => $res->isAdmin,
                ];
        }
        return null;
    }

    public function changePassword(int $userId, string $newPassword)
    {
        $query = "UPDATE systemUsers SET passwordHash=:passwordHash WHERE id=:id";
        $stm = $this->conn->prepare($query);

        $stm->bindParam(':id',$userId);
        $passwordHash = password_hash($newPassword,PASSWORD_DEFAULT);

        $stm->bindParam(':passwordHash',$passwordHash);

        $stm->execute();

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
            $stmt = $this->conn->prepare("INSERT INTO logins (userId,selector,hash,loginDate,expiresAt) VALUES (?,?,?,?,?)");

            $stmt->execute([$userId,$tokenLeft,$tokenRightHashed,$date->format("Y-m-d H:i:s"),$expire_date->format("Y-m-d H:i:s")]);
            $res['token'] = $tokenLeft.':'.$tokenRight;
            $res['expiresAt'] = $expire_date->format("Y-m-d H:i:s");
            return $res;

        } catch (Exception $e) {

        }
        return null;
    }

    public function loginWithToken(string $tokenString) : ?array
    {
        //echo $tokenString;
        //echo $tokenString;
        //$a= strpos($tokenString,":" );
        //var_dump($a);
        if (strpos($tokenString,":" ) === false) {
            return null;
        }
        list($tokenLeft, $tokenRight) = explode(':', $tokenString);
        if ((strlen($tokenLeft) !== 20) || (strlen($tokenRight) !== 44)) {
           return null;
        }
        $tokenRightHashed = hash('sha256', $tokenRight);
        $query = "SELECT userId, hash, expiresAt FROM logins WHERE selector=:selector";
        $stmt = $this->conn->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'LoginInfo');
        $stmt->bindParam(':selector',$tokenLeft);
        $stmt->execute();
        $info = $stmt->fetch();
        //echo var_dump($row);
        if($info instanceof LoginInfo)
        {
            if(hash_equals($info->hash,$tokenRightHashed))
            {
                try
                {
                    $expires_date = new DateTime($info->expiresAt);
                    $current_date = new DateTime('now');
                    if($expires_date > $current_date)
                    {
                        //echo var_dump($row['userId']);
                        $query2 = "SELECT isAdmin FROM users WHERE id = :userId";
                        $stmt2 = $this->conn->prepare($query2);
                        $stmt2->setFetchMode(PDO::FETCH_ASSOC);
                        $stmt2->bindParam(':userId', $info->userId);
                        $stmt2->execute();
                        $usr = $stmt2->fetch();
                        if ($usr) {
                            $isAdmin = $usr['isAdmin'];
                            return [
                                'id' => $info->userId,
                                'isAdmin' => $isAdmin
                            ];
                        }
                    }
                } catch (Exception $e) {
                    return null;
                }
            }
            // return null;
        }
        return null;
    }

    public function getUsernameById($id): ?string
    {
        $query = "SELECT username FROM users WHERE id = :id LIMIT 1";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id', $id);
        $stm->execute();
        $username = $stm->fetch(PDO::FETCH_COLUMN);
        return $username;
    }
}