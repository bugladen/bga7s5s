<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

//Use for Actions on Risk Cards
//Use RiskCityAction instead for City Actions on Risk Cards
class RiskAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }        
        
        $card = $this->getOwningCard($theah);        
        if ($card->Location != Game::LOCATION_HAND && ! $overrideInHandCheck)
        {
            return false;
        }

        return true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // Add characters owned by the player that are in play
        $performers += $theah->getCharactersInPlayByPlayerId($playerId);

        return $performers;
    }
}