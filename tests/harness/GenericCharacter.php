<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

/** Lightweight Character for opposing pieces / Thugs / Mercenaries / Strega in tests. */
class GenericCharacter extends Character
{
    public function __construct(string $name = 'Generic', array $traits = [])
    {
        parent::__construct();
        $this->Name = $name;
        $this->Title = '';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 1;
        $this->Traits = $traits;
        $this->resetCard();
    }
}
