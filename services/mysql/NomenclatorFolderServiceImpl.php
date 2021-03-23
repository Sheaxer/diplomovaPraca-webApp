<?php
require_once ("entities/NomenclatorFolder.php");
require_once (__DIR__ ."/../NomenclatorFolderService.php");

class NomenclatorFolderServiceImpl implements NomenclatorFolderService
{
    private PDO $conn;

    function __construct(PDO $PDO)
    {
        $this->conn=$PDO;
    }

    public function getAllFolders(): ?array
    {
        $query = "SELECT * FROM folders";
        $stm = $this->conn->prepare($query);
        #$stm->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
        $stm->execute();
        $result = $stm->fetchAll(PDO::FETCH_CLASS,"NomenclatorFolder");

        if($result === false)
            return null;

        $q2 = "SELECT r.description AS `description` FROM folderregions INNER JOIN regions r on folderregions.regionId = r.id 
WHERE folderName=:folderName";
        $stm2 = $this->conn->prepare($q2);

        foreach ($result as $i)
        {
            if($i instanceof NomenclatorFolder)
            {
                $stm2->bindParam(':folderName',$i->name);
                $stm2->execute();
                $i->regions = $stm2->fetchAll(PDO::FETCH_COLUMN);
            }
        }
        return $result;
    }

    public function folderExists($name): bool
    {
        $query = "SELECT name FROM folders WHERE name=:name";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':name',$name);
        $stm->execute();
        $data = $stm->fetchColumn(0);
        if($data === false)
            return false;
        else
            return true;
        // TODO: Implement folderExists() method.
    }
}