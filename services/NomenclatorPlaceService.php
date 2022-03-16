<?php

require_once (__DIR__ . '/../entities/Place.php');

interface NomenclatorPlaceService
{
    public function getAllPlaces($limit, $page): array;

    public function getPlaceById($id): ?Place;

    public function createPlace($name): array;

    public function getPlaceByName($name): ?Place;

    public function placeExists($name): bool;
}