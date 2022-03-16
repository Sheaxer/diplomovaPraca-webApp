<?php

require_once (__DIR__ . '/../../entities/Place.php');
require_once (__DIR__ . '/../NomenclatorPlaceService.php');

class NomenclatorPlaceServiceImpl implements NomenclatorPlaceService
{

    private  $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAllPlaces($limit, $page): array
    {
        $query = "SELECT * from places LIMIT :offset, :pageLimit";
        $stm = $this->conn->prepare($query);
        $countQuery = "SELECT COUNT(*) from places";
        $offset = ($page - 1) * $limit;
        $stm->bindParam(':offset', $offset);
        $stm->bindParam(':pageLimit', $limit);
        $stm->execute();
        $countStm = $this->conn->prepare($countQuery);
        $countStm->execute();
        $count = $countStm->fetchColumn(0);
        $places = $stm->fetchAll(PDO::FETCH_CLASS, 'Place');
        $end = $offset + $limit;
        $isNextPage = false;
        if ($end < $count) {
            $isNextPage = true;
        }

        return [
            'count' => $count,
            'nextPage' => $isNextPage,
            'items' => $places,
        ];
    }

    public function getPlaceById($id): ?Place
    {
        $query = "SELECT * from places WHERE id = :id";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':id', $id);
        $stm->setFetchMode(PDO::FETCH_CLASS, 'Place');
        $stm->execute();
        return $stm->fetch();
    }
}