<?php

require_once (__DIR__ ."/../NomenclatorImageService.php");
require_once (__DIR__ . "/../../entities/NomenclatorImage.php");

class NomenclatorImageServiceImpl implements NomenclatorImageService
{

    private $conn;

    function __construct(PDO $PDO)
    {
        $this->conn = $PDO;
    }

    public function createNomenclatorImage(NomenclatorImage $nomenclatorImage,int $nomenclatorKeyId, int $ord)
    {
        $query = "INSERT INTO nomenclatorimages (url,nomenclatorKeyId,isLocal,structure,ord, hasInstructions) VALUES 
(:url,:nomenclatorKeyId,:isLocal,:structure,:ord, :hasInstructions)";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':url',$nomenclatorImage->url);
        $stm->bindParam(':nomenclatorKeyId',$nomenclatorKeyId, PDO::PARAM_INT);
        $stm->bindParam(':isLocal',$nomenclatorImage->isLocal, PDO::PARAM_BOOL);
        $stm->bindParam(':structure',$nomenclatorImage->structure);
        $stm->bindParam(':ord', $ord);
        $stm->bindParam(':hasInstructions',$nomenclatorImage->hasInstructions, PDO::PARAM_BOOL);
        $stm->execute();
    }

    public function getNomenclatorImagesOfNomenclatorKey(int $nomenclatorKeyId): ?array
    {
        $stm = $this->conn->prepare("SELECT `url`, structure, hasInstructions FROM nomenclatorimages WHERE nomenclatorKeyId=:nomenklatorKeyId ORDER BY ord");
        $stm->bindParam(':nomenklatorKeyId',$nomenclatorKeyId);
        $stm->execute();
        $images = $stm->fetchAll(PDO::FETCH_ASSOC);
        if($images === false)
            return null;
        return $images;
    }
}