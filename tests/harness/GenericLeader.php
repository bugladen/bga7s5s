<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

/** Lightweight Leader for assassination / Crew Cap / Panache unit tests. */
class GenericLeader extends Leader
{
    public function __construct(string $name = 'Generic Leader')
    {
        parent::__construct();
        $this->Name = $name;
        $this->Title = '';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 5;
        $this->Panache = 5;
        $this->Traits = ['Leader'];
        $this->resetCard();
    }
}
