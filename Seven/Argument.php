<?php

namespace Seven;

class Argument
{
    protected $DB;
    public function __construct($DB)
    {
        $this->DB = $DB;
    }
}
