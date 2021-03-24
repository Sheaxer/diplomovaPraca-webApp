<?php

require_once ("entities/DigitalizedTranscription.php");
require_once (__DIR__ . "/../DigitalizedTranscriptionService.php");
require_once ("entities/EncryptionPair.php");
class DigitalizedTranscriptionServiceImpl implements DigitalizedTranscriptionService
{

    private PDO $conn;

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
        $query = "SELECT id, digitalizationVersion, note, digitalizationDate, createdBy FROM digitalizedtranscriptions WHERE nomenclatorKeyId=:nomenclatorKeyId";
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
}