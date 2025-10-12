<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use BgaUserException;

class Action_01052 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal a Wound");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->Wounds > 0);
        foreach ($characters as $character)
        {
            if (count($character->Attachments) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($perforer) => count($perforer->Attachments) > 0 && $perforer->Wounds > 0));

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $owner = $this->getOwningCard($event->theah);
            if ($performer->ControllerId != $owner->ControllerId)
            {
                throw new BgaUserException($game->translate("You cannot heal a wound on a character that is not yours."));
            }

            if ($performer->Wounds == 0)
            {
                throw new BgaUserException($game->translate("You cannot heal a wound on a character that is not wounded."));
            }

            $healEvent = EventFactory::createCharacterHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode());
            $event->theah->queueEvent($healEvent);
        }
    }
}