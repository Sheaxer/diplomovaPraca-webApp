<?php

class NomenclatorKeyState
{
    const TABLE_NAME = 'nomenclatorkeystate';

    const STATE_APPROVED = 'approved';
    const STATE_DELETED  = 'deleted';
    const STATE_NEW      = 'new';
    const STATE_REVISE = 'revise';
    const STATE_AWAITING = 'awaiting';

    const AVAILABLE_STATES = [
        self::STATE_APPROVED, self::STATE_DELETED,
        self::STATE_AWAITING, self::STATE_NEW,
        self::STATE_REVISE,
    ];

    public $nomenclatorKeyId;
    public $id;
    public $state;
    public $createdBy;
    public $createdAt;
    public $updatedAt;
    public $note;
    public $createdById;
}