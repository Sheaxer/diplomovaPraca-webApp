<?php

require_once (__DIR__ . '/../StatisticsService.php');
require_once (__DIR__ . '/../../entities/NomenclatorKeyState.php');

class StatisticsServiceImpl implements StatisticsService
{

    private  $conn;

    function  __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getStatistics(): ?array
    {
        $keyCountQuery = "SELECT COUNT(k.id) from nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState";
        $keyCountStm = $this->conn->prepare($keyCountQuery);
        $keyCountStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $keyCountStm->execute();
        $keyCount = $keyCountStm->fetchColumn(0);

        $languageCountQuery = "SELECT COUNT(DISTINCT(`language`)) from nomenclatorkeys";
        $languageCountStm = $this->conn->prepare($languageCountQuery);
        $languageCountStm->execute();
        $languageCount = $languageCountStm->fetchColumn(0);

        $oldestKeyQuery = "SELECT k.usedFrom FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState ORDER BY k.usedFrom ASC LIMIT 1";
        $oldestKeyStm = $this->conn->prepare($oldestKeyQuery);
        $oldestKeyStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $oldestKeyStm->execute();
        $oldestKey = $oldestKeyStm->fetchColumn(0);

        $youngestKeyQuery = "SELECT k.usedTo FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState ORDER BY k.usedTo DESC LIMIT 1";
        $youngestStm = $this->conn->prepare($youngestKeyQuery);
        $youngestStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $youngestStm->execute();
        $youngestKey = $youngestStm->fetchColumn(0);

        $archiveCountQuery = "SELECT COUNT(*) from archives";
        $archiveCountStm = $this->conn->prepare($archiveCountQuery);
        $archiveCountStm->execute();
        $archiveCount = $archiveCountStm->fetchColumn(0);

        $statistics = [
            'keyCount' => intval($keyCount),
            'oldestKey' => $oldestKey,
            'youngestKey' => $youngestKey,
            'languageCount' => intval($languageCount),
            'archiveCount' => intval($archiveCount),
        ];

        return $statistics;
    }
}