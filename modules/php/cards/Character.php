<?php

abstract class Character extends Card
{
    public $Titles = [];
    public int $Resolve;
    public int $Combat;
    public int $Finesse;
    public int $Influence;

    public function __construct()
    {
        parent::__construct();

        $this->Resolve = 0;
        $this->Combat = 0;
        $this->Finesse = 0;
        $this->Influence = 0;
    }
}