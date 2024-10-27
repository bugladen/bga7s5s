<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Leader extends Character
{

    public int $CrewCap;
    public int $ModifiedCrewCap;
    public int $Panache;
    public int $ModifiedPanache;

    public function __construct(){
        parent::__construct();

        $this->CrewCap = 0;
        $this->ModifiedCrewCap = 0;
        $this->Panache = 0;
        $this->ModifiedPanache = 0;
    }
}