<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;

/** Lightweight Brute for EventHub destroy→discard (non-Brutes go to Locker). */
class GenericBrute extends Brute
{
    public function __construct(string $name = 'Generic Brute')
    {
        parent::__construct();
        $this->Name = $name;
        $this->Title = '';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Resolve = 2;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->Traits = ['Brute'];
        $this->resetCard();
    }
}
