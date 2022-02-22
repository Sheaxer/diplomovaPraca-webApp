<?php

require_once (__DIR__ . "/../config/DatabaseConfig.php");

require_once (__DIR__ . "/../config/serviceConfig.php");
require_once (__DIR__ . "/../entities/NomenclatorKeyState.php");

$getService = GETConnection();
$postService = POSTConnection();

$a = "SELECT id from systemusers where isAdmin = 1 LIMIT 1";
$s = $getService->prepare($a);
$s->execute();
$userId = $s->fetch(PDO::FETCH_COLUMN);
var_dump($userId);

$query = "SELECT id from nomenclatorKeys WHERE stateId = 0 OR stateId is NULL";
$stm = $getService->prepare($query);

$stm->execute();
$ids = $stm->fetchAll(PDO::FETCH_COLUMN);
var_dump($ids);

$query2 = "INSERT INTO nomenclatorKeyState (`state`, createdBy, note) VALUES (:st, :createdBy, :note)";
$stm2 = $postService->prepare($query2);
$stm2->bindValue(':st', NomenclatorKeyState::STATE_NEW);
$stm2->bindValue(':createdBy', intval($userId));
$stm2->bindValue(':note', '');

$query3 = "UPDATE nomenclatorKeys SET stateId = :stateId WHERE id=:id";
$stm3 = $postService->prepare($query3);

$postService->beginTransaction();

foreach ($ids as $id) {
    var_dump($id);
    $stm2->execute();
    $stateId = $postService->lastInsertId();
    $stm3->bindParam(':stateId', $stateId);
    $stm3->bindParam(':id', $id);
    $stm3->execute();
}
$postService->commit();