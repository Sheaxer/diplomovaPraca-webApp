<?php

require_once (__DIR__ . "/../entities/NomenclatorImage.php");
interface NomenclatorImageService
{
    public function createNomenclatorImage(NomenclatorImage $nomenclatorImage, int $nomenclatorKeyId, int $ord);

    public function getNomenclatorImagesOfNomenclatorKey(int $nomenclatorKeyId): ?array;
}