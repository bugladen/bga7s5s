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

        $daniella = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($daniella))
            return false;

        $characters = $theah->getCharactersAtLocation($daniella->Location);

        $mercenaries = array_filter($characters, fn($character) => $character->ControllerId == $daniella->ControllerId && $character->hasTrait("Mercenary", $daniella));
        if (count($mercenaries) == 0)
            return false;

        $opposing = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($playerId));
        if (count($opposing) == 0)
            return false;

        return true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $daniella = $this->getOwningCharacter($theah);
        $performers = $theah->getCharactersAtLocation($daniella->Location);
        $performers = array_filter($performers, fn($performer) => $performer->ControllerId == $daniella->ControllerId && $performer->hasTrait("Mercenary", $daniella));

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

            $daniella = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($daniella->ControllerId, $daniella->Id, "01036", $this->Id);
            $event->theah->queueEvent($transitionEvent);

            $this->setUsed($event->theah, true);
        }
    }

}