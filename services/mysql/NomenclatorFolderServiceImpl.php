<?php
require_once (__DIR__ ."/../../entities/NomenclatorFolder.php");
require_once (__DIR__ ."/../NomenclatorFolderService.php");
require_once (__DIR__ . "/../../entities/Region.php");
require_once (__DIR__ . "/../../entities/Archive.php");
require_once (__DIR__ . "/RegionServiceImpl.php" );

class NomenclatorFolderServiceImpl implements NomenclatorFolderService
{
    private  $conn;

    function __construct(PDO $PDO)
    {
        $this->conn=$PDO;
    }

    public function getAllFolders($limit, $page): ?array
    {
        $query = "SELECT * FROM folders";
        if ($limit ) {
            $query .= " LIMIT :offset, :pageLimit";
        }
        $stm = $this->conn->prepare($query);
        if ($limit) {
            $offset = ($page -1) * $limit;
            $stm->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stm->bindParam(':pageLimit', $limit, PDO::PARAM_INT);
        }
       
        #$stm->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
        $stm->execute();
        $result = $stm->fetchAll(PDO::FETCH_CLASS,"NomenclatorFolder");

        if($result === false)
            return null;

        $q2 = "SELECT r.* FROM folderregions INNER JOIN regions r on folderregions.regionId = r.id 
WHERE folderName=:folderName";

        $q3 = "SELECT f.`name` as `name`, a.shortName as shortName, a.country as country, a.name as archiveName from fonds f INNER JOIN folders fold on fold.fond = f.name INNER JOIN archives a ON f.archive = a.shortName where fold.name = :folderName";

        $stm2 = $this->conn->prepare($q2);

        $stm3 = $this->conn->prepare($q3);

        foreach ($result as $i)
        {
            if($i instanceof NomenclatorFolder)
            {
                $stm2->bindParam(':folderName',$i->name, PDO::PARAM_STR);
                $stm2->execute();
                $i->regions = $stm2->fetchAll(PDO::FETCH_CLASS, 'Region');

                $stm3->bindParam(":folderName", $i->name);
                $stm3->execute();
                $tmp = $stm3->fetch(PDO::FETCH_ASSOC);
                $fond = new Fond();
                $fond->name = $tmp['name'];
                $archive = new Archive();
                $archive->country = $tmp['country'];
                $archive->name = $tmp['archiveName'];
                $archive->shortName = $tmp['shortName'];
                $fond->archive= $archive;
                $i->fond = $fond;
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

    public function getFolderByName($name)
    {
        $q = "SELECT * from folders where `name` = :folderName";
        $stm = $this->conn->prepare($q);
        $stm->setFetchMode(PDO::FETCH_CLASS, 'NomenclatorFolder');
        $stm->bindParam(":folderName", $name, PDO::PARAM_STR);
        $stm->execute();
        $folder = $stm->fetch();
        if ($folder && $folder instanceof NomenclatorFolder) {
            $q2 = "SELECT r.* AS `description` FROM folderregions INNER JOIN regions r on folderregions.regionId = r.id 
            WHERE folderName=:folderName";
            
            $q3 = "SELECT f.`name` as `name`, a.shortName as shortName, a.country as country, a.name as archiveName from fonds f INNER JOIN folders fold on fold.fond = f.name INNER JOIN archives a ON f.archive = a.shortName where fold.name = :folderName";
    
            $stm2 = $this->conn->prepare($q2);
    
            $stm3 = $this->conn->prepare($q3);

            $stm2->bindParam(':folderName',$folder->name, PDO::PARAM_STR);
            $stm2->execute();
            $folder->regions = $stm2->fetchAll(PDO::FETCH_CLASS, 'Region');

            $stm3->bindParam(":folderName", $folder->name);
            $stm3->execute();
            $tmp = $stm3->fetch(PDO::FETCH_ASSOC);
            $fond = new Fond();
            $fond->name = $tmp['name'];
            $archive = new Archive();
            $archive->country = $tmp['country'];
            $archive->name = $tmp['archiveName'];
            $archive->shortName = $tmp['shortName'];
            $fond->archive= $archive;
            $folder->fond = $fond;
            return $folder;
        }
        return null;

       
    }

    public function createFolder(NomenclatorFolder $folder)
    {
        //xdebug_break();
        $query = "INSERT INTO folders (`name`, fond, startDate, endDate) VALUES (:folderName, :fond, :startDate, :endDate)";

        $stm = $this->conn->prepare($query);

        $existsFond = "SELECT `name` from fonds WHERE `name` = :fondName LIMIT 1";
        $this->conn->beginTransaction();
        $existsFondStm = $this->conn->prepare($existsFond);
        $existsFondStm->bindParam(":fondName", $folder->fond->name, PDO::PARAM_STR);
        $existsFondStm->execute();
        $ex = $existsFondStm->fetch(0);
        if ($ex == null) {
            $existsArchive = "SELECT shortName from archives WHERE shortName = :shortName";
            $existsArchiveStm = $this->conn->prepare($existsArchive);
            $existsArchiveStm->bindParam(":shortName", $folder->fond->archive->shortName, PDO::PARAM_STR);
            $existsArchiveStm->execute();
            $existsArchiveStm->execute();
            $eA = $existsArchiveStm->getAttribute(0);
            if ($eA == null) {
               
                $insertArchive = "INSERT INTO archives (shortName, country, `name`) VALUES (:shortName, :country, :archiveName)";
                $insertArchiveSmt = $this->conn->prepare($insertArchive);
                $insertArchiveSmt->bindParam(":shortName", $folder->fond->archive->shortName, PDO::PARAM_STR);
                $insertArchiveSmt->bindParam(":country", $folder->fond->archive->country, PDO::PARAM_STR);
                $insertArchiveSmt->bindParam(":archiveName", $folder->fond->archive->name, PDO::PARAM_STR);

                $r = $insertArchiveSmt->execute();

                if (! $r) {
                    $errorCode = $this->conn->errorInfo()[2];
                    $this->conn->rollBack();
                    return [
                        'status' => 'error',
                        'error' => $errorCode
                    ];
                }
            }

            $insertFond = "INSERT INTO fonds (`name`, archive) VALUES (:fondName, :archive)";
            $insertFondSmt = $this->conn->prepare($insertFond);
            $insertFondSmt->bindParam(":fondName", $folder->fond->name, PDO::PARAM_STR);
            $insertFondSmt->bindParam(":archive", $folder->fond->archive->shortName, PDO::PARAM_STR);

            $r = $insertFondSmt->execute();
            if (! $r) {
                $errorCode = $this->conn->errorInfo()[2];
                $this->conn->rollBack();
                return [
                    'status' => 'error',
                    'error' => $errorCode
                ];
            }
        }

        $stm->bindParam(":folderName", $folder->name, PDO::PARAM_STR);
        $stm->bindParam(":fond", $folder->fond->name, PDO::PARAM_STR);
        $stm->bindValue(":startDate", $folder->startDate ? $folder->startDate->format('Y-m-d H:i:s') : null);
        $stm->bindValue(":endDate", $folder->endDate ? $folder->endDate->format('Y-m-d- H:i:s') : null);

        $r = $stm->execute();

        if (! $r) {
            $errorCode = $this->conn->errorInfo()[2];
            $this->conn->rollBack();
            return [
                'status' => 'error',
                'error' => $errorCode
            ];
        }

        $regionService = new RegionServiceImpl($this->conn);

        /** @var Region $region */
        if ($folder->regions && ! empty($folder->regions)) {
            foreach ($folder->regions as $region) {
                if ($region->id) {
                    $r = $regionService->getRegionById($region->id);
                    if (! $r) {
                        $this->conn->rollBack();
                        return [
                            'status' => 'error',
                            'error' => 'Invalid region'
                        ];
                    }
    
                    $regionService->addRegionToFolder($region->id, $folder->name);
                } else {
                    $ret = $regionService->createRegion($region, false);
                    if ($ret['status'] == 'success') {
                        $regionService->addRegionToFolder($ret['id'], $folder->name);
                    } else {
                        $this->conn->rollBack();
                        return [
                            'status' => 'error',
                            'error' => $ret['error']
                        ];
                    }
                }
            }
        }


        $this->conn->commit();
        return [
            'status' => 'success'
        ];


    }
}