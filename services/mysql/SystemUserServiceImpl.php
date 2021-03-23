<?php

require_once ("entities/SystemUser.php");
require_once (__DIR__ ."/../SystemUserService.php");

require_once (__DIR__ . "/../../entities/LoginInfo.php");

class SystemUserServiceImpl implements SystemUserService
{
    private PDO $conn;
    function __construct(PDO $PDO)
    {
        $this->conn = $PDO;
    }

    public function createSystemUser(string $userName, string $password): int
    {
        $query = "INSERT INTO systemusers (username, passwordHash) VALUES (:username,:passwordHash)";
        $stm = $this->conn->prepare($query);

        $stm->bindParam(':username',$userName);
        $password_hash = password_hash($password);
        $stm->bindParam(':passwordHash', $password_hash);
        $this->conn->beginTransaction();
        $stm->execute();

        $addedId = intval($this->conn->lastInsertId());
        $this->conn->commit();
        return $addedId;
    }

    public function logIn(string $userName, string $password): ?int
    {
        $query= "SELECT id,passwordHash FROM systemusers WHERE username=:username";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':username',$userName);

        $stm->execute();

        $res = $stm->fetchObject('SystemUser');
        if($res instanceof SystemUser)
        {
            if(password_verify($password,$res->passwordHash))
                return $res->id;
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
}