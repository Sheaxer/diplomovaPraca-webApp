<?php

class NomenclatorKeyState
{
    const TABLE_NAME = 'nomenclatorkeystate';

    const STATE_APPROVED = 'approved';
    const STATE_DELETED  = 'deleted';
    const STATE_NEW      = 'new';
    const STATE_REJECTED = 'rejected';
    const STATE_AWAITING = 'awaiting';

    public $nomenclatorKeyId;
    public $id;
    public $state;
    public $createdBy;
    public $createdAt;
    public $updatedAt;
    public $note;
    public $createdById;
}