<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CityAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $card = $this->getOwningCard($theah);
        $location = $card->Location;

        //Any characters in play at location owned by player?
        $characters = $theah->getCharactersAtLocation($location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId == $playerId);

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        //Get characters in play owned by playerId
        $performers += $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_values(array_filter($performers, fn($character) => $character->Location == $this->getOwningCard($theah)->Location));

        return $performers;
    }
}