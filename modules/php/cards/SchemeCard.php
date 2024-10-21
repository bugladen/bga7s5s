<?php

abstract class SchemeCard extends Card
{
    public int $Initiative;
    public int $Panache;

    public function __construct($type, $value)
    {
        parent::__construct($type, $value);

        $this->Initiative = 0;
        $this->Panache = 0;
    }
}