<?php

class NomenclatorKeyState
{
    const TABLE_NAME = 'nomenclatorKeyState';

    const STATE_APPROVED = 'approved';
    const STATE_DELETED  = 'deleted';
    const STATE_NEW      = 'new';

    public $nomenclatorKeyId;
    public $id;
    public $state;
    public $createdBy;
    public $createdAt;
    public $uploadedAt;
    public $note;
    public $createdById;
}