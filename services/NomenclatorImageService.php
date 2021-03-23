<?php

require_once ("entities/NomenclatorImage.php");
interface NomenclatorImageService
{
    public function createNomenclatorImage(NomenclatorImage $nomenclatorImage, int $nomenclatorKeyId, int $ord);

    public function getNomenclatorImagesOfNomenclatorKey(int $nomenclatorKeyId): ?array;
}