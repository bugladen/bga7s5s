<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class EventCityAction extends Action
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
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

    public function getCharactersForAction(int $playerId, Theah $theah): array
    {
        $characters = parent::getCharactersForAction($playerId, $theah);

        $characters += $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_values(array_filter($characters, fn($character) => $character->Location == $this->getOwningCard($theah)->Location));

        return $characters;
    }
}