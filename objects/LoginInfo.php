<?php


class LoginInfo
{
    private string $table_name = "logins";
    private int $hour_number = 48;


    private ?PDO $db;

    public function __construct(PDO $db)
    {
        $this->$db = $db;
    }

    public function getHoursToExpire():int
    {
        return $this->hour_number;
    }

    public function createToken(int $user_id): ?string
    {
        try {
            $tokenLeft = base64_decode(random_bytes(15));
            $tokenRight = base64_encode(random_bytes(33));
            $tokenHashed = hash('sha256', $tokenRight);

            $date = new DateTime('now');

            $expire_date = new DateTime('now');
            $expire_date->add(new DateInterval("PT". strval($this->hour_number) . "H"));

            $stmt = $this->db->prepare("INSERT INTO ". $this->table_name . " (user,selector,hash,loginDate,expiresAt) VALUES
            (?,?,?,?,?)");

            $stmt->execute([$user_id, $tokenLeft,$tokenHashed,$date,$expire_date]);

            return $tokenLeft.':'.$tokenHashed;

        } catch (Exception $e) {
        }
        return null;
    }

    public function loginWithCookie(string $cookie_string) : ?int
    {
        if (strpos(':', $cookie_string) === false) {
            throw new Exception('Invalid authentication token');
        }
        list($tokenLeft, $tokenRight) = explode(':', $cookie_string);
        if ((strlen($tokenLeft) !== 20) || (strlen($tokenRight) !== 44)) {
            throw new Exception('Invalid authentication token');
    }
        $tokenRightHashed = hash('sha256', $tokenRight);

        $stmt = $this->db->prepare("SELECT * FROM " . $this->table_name . " WHERE selector = ?");
        $stmt->execute([$tokenLeft]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if(hash_equals($row['hash'],$tokenRightHashed))
        {
            try {
                $expires_date = new DateTime($row['expiresAt']);
                $current_date = new DateTime('now');
                if($expires_date > $current_date)
                {
                    return $row['user'];
                }
            } catch (Exception $e) {
                throw new Exception('Invalid authentication token');
            }
        }
        else throw new Exception('Invalid authentication token');
        return null;
    }
}