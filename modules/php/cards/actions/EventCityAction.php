<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class EventCityAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $card = $this->getOwningCard($theah);

        // WHY: City Actions require the event in a city location. _03cd20 can sit in a
        // player's Home after a successful pressure; without this gate the action
        // reappears whenever that player has characters at Home (same Location string).
        if (! $theah->cardInCity($card))
        {
            return false;
        }

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