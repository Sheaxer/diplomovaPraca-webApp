<?php

require_once (__DIR__ . '/../entities/Place.php');

interface NomenclatorPlaceService
{
    public function getAllPlaces($limit, $page): array;

    public function getPlaceById($id): ?Place;
}