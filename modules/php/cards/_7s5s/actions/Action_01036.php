<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01036 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Your Mercenary Issues Challenge");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $daniela = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($daniela))
            return false;

        $characters = $theah->getCharactersAtLocation($daniela->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId == $daniela->ControllerId && $character->hasTrait("Mercenary"));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $daniela = $this->getOwningCharacter($theah);
        $performers = $theah->getCharactersAtLocation($daniela->Location);
        $performers = array_filter($performers, fn($performer) => $performer->ControllerId == $daniela->ControllerId && $performer->hasTrait("Mercenary"));

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $game->globals->set(Game::CHALLENGE_TYPE, Game::DANIELA_DEITRICH_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $daniela = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($daniela->ControllerId, $daniela->Id, "01036", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

}