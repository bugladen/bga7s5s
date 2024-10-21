<?php

abstract class Leader extends Character{

    public int $CrewCap;
    public int $Panache;

    public function __construct(){
        parent::__construct();

        $this->CrewCap = 0;
        $this->Panache = 0;
    }
}