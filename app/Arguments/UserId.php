<?php

namespace App\Arguments;

use Seven\Argument;

class UserId extends Argument
{
    public $tableName = 'users';
    protected $userId;

    public function __construct($DB, $userId)
    {
        parent::__construct($DB);
        $this->userId = $userId;
    }

    public function entityExists()
    {
        return $this->DB->existsById('users', $this->userId);
    }

    public function __toString()
    {
        return $this->userId;
    }
}
