<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;

class Reaction_01008 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Copy Sorcerer Ability Just Played");
    }
}