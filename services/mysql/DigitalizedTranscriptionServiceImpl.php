<?php

require_once ("entities/DigitalizedTranscription.php");
require_once (__DIR__ . "/../DigitalizedTranscriptionService.php");
require_once ("entities/EncryptionPair.php");
class DigitalizedTranscriptionServiceImpl implements DigitalizedTranscriptionService
{

    private  $conn;

    function __construct(PDO $PDO)
    {
        $this->conn = $PDO;
    }

    public function createDigitalizedTranscription(DigitalizedTranscription $transcription, int $nomenclatorId, int $createdBy): int
    {
        $query = "INSERT INTO digitalizedtranscriptions (nomenclatorKeyId, digitalizationVersion, note, digitalizationDate, createdBy) 
VALUES (:nomenclatorKeyId,:digitalizationVersion,:note,:digitalizationDate,:createdBy)";

        $stm = $this->conn->prepare($query);
        $stm->bindParam(':nomenclatorKeyId',$nomenclatorId);
        $stm->bindParam(':digitalizationVersion',$transcription->digitalizationVersion);
        $stm->bindParam(':note',$transcription->note);
        $date = date("Y-m-d H:i:s");
        $stm->bindParam(':digitalizationDate', $date);
        $stm->bindParam(':createdBy',$createdBy);

        $query2="INSERT INTO encryptionpairs (digitalizedTranscriptionId, plainTextUnit, cipherTextUnit) VALUES
(:digitalizedTranscriptionId,:plainTextUnit,:cipherTextUnit)";

        $stm2 = $this->conn->prepare($query2);

        $this->conn->beginTransaction();

        $stm->execute();
        $digitalizedTranscriptionId = intval($this->conn->lastInsertId());

        foreach ($transcription->encryptionPairs as $pair)
        {
            //var_dump($pair);
            if($pair instanceof EncryptionPair)
            {
                $stm2->bindParam(':plainTextUnit',$pair->plainTextUnit);
                $stm2->bindParam(':cipherTextUnit',$pair->cipherTextUnit);
            }
            else if (is_array($pair))
            {
                $stm2->bindParam(':plainTextUnit',$pair['plainTextUnit']);
                $stm2->bindParam(':cipherTextUnit',$pair['cipherTextUnit']);
            }
            else continue;
            $stm2->bindParam(':digitalizedTranscriptionId',$digitalizedTranscriptionId);
            $stm2->execute();
        }
        $this->conn->commit();
        return $digitalizedTranscriptionId;

    }

    public function getDigitalizedTranscriptionsOfNomenclator(int $nomenclatorId): ?array
    {
        $query = "SELECT n.id as id, n.digitalizationVersion as digitalizationVersions, n.note as note, n.digitalizationDate as digitalizationDate, u.username as uploadedBy 
         FROM digitalizedtranscriptions n inner join systemusers u on n.createdBy = u.id WHERE nomenclatorKeyId=:nomenclatorKeyId";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':nomenclatorKeyId',$nomenclatorId);
        $stm->execute();
        $result = $stm->fetchAll(PDO::FETCH_ASSOC);
        if($result === false)
            return null;
        return $result;
    }

    public function getDigitalizedTranscriptionById($id): ?DigitalizedTranscription
    {
        $query = "SELECT * FROM digitalizedtranscriptions WHERE id=:id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id',$id);
        $stm->execute();
        $res = $stm->fetchObject('DigitalizedTranscription');
        if($res instanceof DigitalizedTranscription)
        {
            $res->encryptionPairs = $this->getEncryptionPairsByTranscriptionId($id);
            return $res;
        }
        return null;
    }

    public function getEncryptionPairsByTranscriptionId(int $id): ?array
    {
        $query = "SELECT plainTextUnit, cipherTextUnit FROM encryptionpairs WHERE digitalizedTranscriptionId=:id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id', $id);
        $stm->execute();
        $data = $stm->fetchAll(PDO::FETCH_ASSOC);
        if ($data === null | $data === false)
            return null;
        return $data;
    }

    public function getEncryptionKeyByTranscriptionId(int $id): ?array
    {
        $data = $this->getEncryptionPairsByTranscriptionId($id);
        if($data === null)
            return null;
        $result = array();
        foreach ($data as $d)
        {
            if(!array_key_exists($d["plainTextUnit"],$result))
            {
                $result[$d["plainTextUnit"]] = array();
            }
            array_push($result[$d["plainTextUnit"]], $d["cipherTextUnit"]);
        }
        return $result;
    }

    public function getDecryptionKeyByTranscriptionId(int $id): ?array
    {
        $data = $this->getEncryptionPairsByTranscriptionId($id);
        if($data === null)
            return null;
        $result = array();
        foreach ($data as $d)
        {
            $result[$d["cipherTextUnit"]] = $d["plainTextUnit"];
        }
        return $result;
    }

    public function getAllTranscriptions(): ?array
    {
        $query1 = "SELECT id, digitalizationDate, digitalizationVersion, nomenclatorKeyId FROM digitalizedtranscriptions";
        $stm1 = $this->conn->prepare($query1);
        $stm1->execute();
        $data = $stm1->fetchAll(PDO::FETCH_ASSOC);
        $query2 = "SELECT id,folder,signature,completeStructure FROM nomenclatorkeys WHERE id=:id";

        $stm2 = $this->conn->prepare($query2);
        $query3 = "SELECT name from keyusers join nomenclatorkeyusers n on keyusers.id = n.userId where n.nomenclatorKeyId=:keyId";
        $stm3 = $this->conn->prepare($query3);
        $result = array();
        foreach ($data as $d)
        {
            if($d['nomenclatorKeyId'] !== null)
            {
                $stm2->bindParam(":id",$d['nomenclatorKeyId']);
                $stm2->execute();
                $d['nomenclatorKey'] = $stm2->fetch(PDO::FETCH_ASSOC);
            }

            $stm3->bindParam(":keyId",$d['nomenclatorKeyId']);
            $stm3->execute();
            $keyUsers =$stm3->fetchAll(PDO::FETCH_ASSOC);
            if($keyUsers !== false)
                $d['nomenclatorKey']['keyUsers'] = $keyUsers;
            unset($d['nomenclatorKeyId']);
            array_push($result,$d);
        }
        if(empty($result))
            return null;
        return $result;
    }
}