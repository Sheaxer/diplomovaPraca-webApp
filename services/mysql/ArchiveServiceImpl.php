<?php

require_once(__DIR__ . '/../ArchiveService.php');
require_once(__DIR__ ."/../../entities/DisplayArchive.php");
require_once(__DIR__ ."/../../entities/DisplayFond.php");
require_once(__DIR__ ."/../../entities/DisplayFolder.php");
require_once(__DIR__ ."/../../entities/Region.php");

class ArchiveServiceImpl implements ArchiveService
{

    private  $conn;

    function __construct(PDO $PDO)
    {
        $this->conn = $PDO;
    }

    public function getArchives(?int $limit, ?int $page): array
    {
        //xdebug_break();
        $countQuery = "SELECT COUNT(*) from archives";

        $countStm = $this->conn->prepare($countQuery);
        $countStm->setFetchMode(PDO::FETCH_ASSOC);
        $countStm->execute();
        $count =intval($countStm->fetchColumn(0));
        $isNextPage = false;
        $query = "SELECT * from archives";
        if ($limit) {
            $offset = ($page-1)*$limit;
            $query.= " LIMIT :offset, :pageLimit";
            
            if (($offset + $limit) < $count) {
                $isNextPage = true;
            }
        }

        $stm = $this->conn->prepare($query);
        if ($limit) {
            $stm->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stm->bindParam(":pageLimit", $limit, PDO::PARAM_INT);
        }

        $stm->execute();

        $archives = $stm->fetchAll(PDO::FETCH_CLASS, DisplayArchive::class);

        $fondQuery = "SELECT * FROM fonds where archive=:archive";
        $fondStatement = $this->conn->prepare($fondQuery);

        $folderQuery = "SELECT * from folders where fond=:fond";
        $folderStatement = $this->conn->prepare($folderQuery);

        $regionQuery = "SELECT r.* from regions r INNER JOIN folderregions f ON r.id = f.regionId  where f.folderName = :folderName";
        $regionStatement = $this->conn->prepare($regionQuery);

        /** @var DisplayArchive $archive */
        foreach ($archives as $archive) {
            $fondStatement->bindParam(":archive", $archive->shortName, PDO::PARAM_STR);
            $fondStatement->execute();
            $archive->fonds = $fondStatement->fetchAll(PDO::FETCH_CLASS, DisplayFond::class);
            /** @var DisplayFond $fond */
            foreach ($archive->fonds as $fond) {
                $folderStatement->bindParam(":fond", $fond->name);
                $folderStatement->execute();
                $fond->folders = $folderStatement->fetchAll(PDO::FETCH_CLASS, DisplayFolder::class);
                unset($fond->archive);

                /** @var DisplayFolder $folder */
                foreach ($fond->folders as $folder) {
                    unset ($folder->fond);
                    $regionStatement->bindParam(":folderName", $folder->name, PDO::PARAM_STR);
                    $regionStatement->execute();
                    $folder->regions = $regionStatement->fetchAll(PDO::FETCH_CLASS, Region::class);
                }
            }
        }
        return [
            'count'    => $count,
            'nextPage' => $isNextPage,
            'items'    => $archives,
        ];
    }
}