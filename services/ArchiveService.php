<?php

require_once(__DIR__ . "/../entities/DisplayArchive.php");

interface ArchiveService
{
    public function getArchives(?int $limit, ?int $page): array;
}