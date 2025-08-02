<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CharacterAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        //Character actions are almost always performed by the character card itself
        $card = $this->getOwningCard($theah);
        return [$card];
    }
}