<?php

require_once (__DIR__ . '/../RegionService.php');

class RegionServiceImpl implements RegionService
{

    private  $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getRegionById(int $id): ?Region
    {
        $query = 'SELECT * from regions where id=:id';
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id', $id, PDO::PARAM_INT);
        $stm->setFetchMode(PDO::FETCH_CLASS, 'Region');
        return $stm->fetch();
    }

    public function createRegion(Region $region, bool $doTransaction = true): ?array
    {
        $query = 'INSERT INTO regions (`description`) VALUES (:descriptionVal)';
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':descriptionVal', $region->description, PDO::PARAM_STR);
        if ($doTransaction) {
            $this->conn->beginTransaction();
        }
        $r = $stm->execute();
        if ($r) {
            $ret = [
                'status' => 'success',
                'id' => $this->conn->lastInsertId(),
            ];
        } else {
            $ret = [
                'status' => 'error',
                'error' => $this->conn->errorInfo()[2],
            ];
            
        }
        if ($doTransaction) {
            $this->conn->commit();
        }
        return $ret;
    }

    public function getRegions(?int $limit, ?int $page): ?array
    {
        $query = 'SELECT * from regions';
        if ($limit) {
            if (! $page) {
                $page = 1;
            }
            $offset = ($page -1 ) * $limit;

            $query .= ' LIMIT :offset, :pageLimit';
        }

        $stm = $this->conn->prepare($query);

        if ($limit) {
            $stm->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stm->bindParam(':pageLimit', $limit, PDO::PARAM_INT);
        }

        return $stm->fetchAll(PDO::FETCH_CLASS, 'Region');

    }

    public function addRegionToFolder($regionId, $folderName)
    {
        $q = "INSERT INTO folderregions (folderName, regionId) VALUES (:folderName, :regionId)";
        $stm = $this->conn->prepare($q);
        $stm->bindParam(":folderName", $folderName, PDO::PARAM_STR);
        $stm->bindParam(":regionId", $regionId, PDO::PARAM_INT);
        $stm->execute();
    }
}