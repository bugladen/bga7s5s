<?php

abstract class Scheme extends Card
{
    public int $Initiative;
    public int $Panache;

    public function __construct()
    {
        parent::__construct();

        $this->Initiative = 0;
        $this->Panache = 0;
    }
}