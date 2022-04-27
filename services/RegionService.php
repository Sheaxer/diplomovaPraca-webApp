<?php

require_once (__DIR__ . '/../entities/Region.php');

interface RegionService
{

    public function getRegions(?int $limit, ?int $page): ?array;

    public function getRegionById(int $id): ?Region;

    public function createRegion(Region $region, bool $doTransaction = true): ?array;
}