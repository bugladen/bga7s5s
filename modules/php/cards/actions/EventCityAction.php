<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class EventCityAction extends CardAction
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
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        foreach ($characters as $character)
        {
            if ($character->Location == $location)
            {
                return true;
            }
        }

        return false;
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