<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01073 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Start Finesse Challenge");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $owner = $this->getOwningCharacter($theah);


        if ( ! $theah->cardInCity($owner))
        {
            return false;
        }

        if ($owner->Engaged)
        {
            return false;
        }

        if (! $owner->canChallenge() || $owner->Engaged)
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId != $owner->ControllerId);

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::CAVALIER_HAT_CHALLENGE_TYPE);

            $owner = $this->getOwningCharacter($event->theah);
            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '01073', $this->Id);
            $event->theah->queueEvent($transition);

            //resetPlayerPassCount is called in stSetupChallenge
            // $this->setUsed() is called in stSetupChallenge
        }
    }
}