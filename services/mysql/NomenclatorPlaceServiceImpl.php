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
        $stm->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stm->bindParam(':pageLimit', $limit, PDO::PARAM_INT);
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
        $stm->bindParam(':id', $id, PDO::PARAM_INT);
        $stm->setFetchMode(PDO::FETCH_CLASS, 'Place');
        $stm->execute();
        return $stm->fetch();
    }

    public function createPlace($name): array
    {
        $query = 'INSERT INTO places (`name`) VALUES (:placeName)';
        $this->conn->beginTransaction();
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':placeName', $name);
        $wasSuccess = $stm->execute();
        if ($wasSuccess) {
            $addedId = $this->conn->lastInsertId();
            $this->conn->commit();
            return [
                'success' => true,
                'id'      => $addedId,
            ];
        } else {
            $errors = $this->conn->errorInfo();
            return [
                'success' => false,
                'error'   => $errors[2],
            ];
        }
        
    }

    public function getPlaceByName($name): ?Place
    {
        $query = "SELECT * from places WHERE `name` = :placeName LIMIT 1";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':placeName', $name);
        $stm->setFetchMode(PDO::FETCH_CLASS, 'Place');
        $stm->execute();
        $place = $stm->fetch();
        if ($place) {
            return $place;
        }
        return null;
    }

    public function placeExists($name): bool
    {
        $query = "SELECT COUNT(id) from places WHERE `name` = :placeName LIMIT 1";
        $stm = $this->conn->prepare($query);
        $stm->bindParam(':placeName', $name);
        $stm->execute();
        $res = $stm->fetchColumn(0);
        if ($res) {
            return true;
        }
        return false;
    }
}