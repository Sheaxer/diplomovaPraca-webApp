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

        /*$oldestKeyQuery = "SELECT k.usedFrom FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState AND k.usedFrom IS NOT NULL ORDER BY k.usedFrom ASC LIMIT 1";
        $oldestKeyStm = $this->conn->prepare($oldestKeyQuery);
        $oldestKeyStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $oldestKeyStm->execute();
        $oldestKey = $oldestKeyStm->fetchColumn(0);

        $youngestKeyQuery = "SELECT k.usedTo FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState AND k.usedTo IS NOT NULL ORDER BY k.usedTo DESC LIMIT 1";
        $youngestStm = $this->conn->prepare($youngestKeyQuery);
        $youngestStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $youngestStm->execute();
        $youngestKey = $youngestStm->fetchColumn(0);*/

        $oldestUsedFrom = 'SELECT k.usedFrom FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState AND k.usedFrom IS NOT NULL ORDER BY k.usedFrom ASC LIMIT 1';
        $oldestUsedFromStm = $this->conn->prepare($oldestUsedFrom);
        $oldestUsedFromStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $oldestUsedFromStm->execute();
        $oldestFrom = $oldestUsedFromStm->fetchColumn(0);

        $oldestUsedTo = 'SELECT k.usedTo FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState AND k.usedTo IS NOT NULL ORDER BY k.usedTo ASC LIMIT 1';
        $oldestUsedToStm = $this->conn->prepare($oldestUsedTo);
        $oldestUsedToStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $oldestUsedToStm->execute();
        $oldestTo = $oldestUsedToStm->fetchColumn(0);

        $youngestUsedFrom = 'SELECT k.usedFrom FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState AND k.usedFrom IS NOT NULL ORDER BY k.usedFrom DESC LIMIT 1';
        $youngestUsedFromStm = $this->conn->prepare($youngestUsedFrom);
        $youngestUsedFromStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $youngestUsedFromStm->execute();
        $youngestFrom = $youngestUsedFromStm->fetchColumn(0);

        $youngestUsedTo = 'SELECT k.usedTo FROM nomenclatorkeys k INNER JOIN nomenclatorkeystate s ON k.stateId = s.id WHERE s.state = :approvedState AND k.usedTo IS NOT NULL ORDER BY k.usedTo DESC LIMIT 1';
        $youngestUsedToStm = $this->conn->prepare($youngestUsedTo);
        $youngestUsedToStm->bindValue(":approvedState", NomenclatorKeyState::STATE_APPROVED);
        $youngestUsedToStm->execute();
        $youngestTo = $youngestUsedToStm->fetchColumn(0);

        $oldestKey = null;
        $youngestKey = null;

        if ($oldestFrom && $oldestTo) {
            $a = new DateTime($oldestFrom);
            $b = new DateTime($oldestTo);
            if ($a < $b) {
                $oldestKey = $oldestFrom;
            } else {
                $oldestKey = $oldestTo;
            }
        } else if ($oldestFrom) {
            $oldestKey = $oldestFrom;
        } else {
            $oldestKey = $oldestTo;
        }

        if ($youngestFrom && $youngestTo) {
            $a = new DateTime($youngestFrom);
            $b = new DateTime($youngestTo);
            if ($a < $b) {
                $youngestKey = $youngestFrom;
            } else {
                $youngestKey = $youngestTo;
            }
        } else if ($youngestFrom) {
            $youngestKey = $youngestFrom;
        } else {
            $youngestKey = $youngestTo;
        }

        /*if (! $oldestKey) {
            $oldestKey = 'N/A';
        }

        if (! $youngestKey) {
            $youngestKey = 'N/A';
        }*/

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
