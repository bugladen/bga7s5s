<?php

abstract class LeaderCard extends Character{
    public function __construct($type, $value){
        parent::__construct($type, $value);

        $this->WealthCost = 0;
    }
}