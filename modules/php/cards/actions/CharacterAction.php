<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CharacterAction extends Action
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
        
       $card = $this->getOwningCard($theah);
       return $card?->ControllerId == $playerId;
    }

    public function getCharactersForAction(int $playerId, Theah $theah): array
    {
        $card = $this->getOwningCard($theah);
        return [$card];
     }
}