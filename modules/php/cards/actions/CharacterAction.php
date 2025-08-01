<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CharacterAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        //If owning card is not controlled by player, do not show as available
        $card = $this->getOwningCard($theah);
        return $card->ControllerId == $playerId;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        //Character actions are almost always performed by the character card itself
        $card = $this->getOwningCard($theah);
        return [$card];
    }
}