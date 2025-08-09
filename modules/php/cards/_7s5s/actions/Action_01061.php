<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01061 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Equipped Performer");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => count($character->Attachments) > 0 && $character->Engaged);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        parent::getPerformersForAction($playerId, $theah);

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_values(array_filter($characters, fn($character) => count($character->Attachments) > 0 && $character->Engaged));
        return $characters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if (! $performer->Engaged)
            {
                throw new \BgaUserException($game->translate("Character must be engaged to use this action"));
            }

            if (count($performer->Attachments) == 0)
            {
                throw new \BgaUserException($game->translate("Character must have an attachment to use this action"));
            }

            $owner = $this->getOwningCard($event->theah);
            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $performer->Id, $owner->Id);
            $event->theah->queueEvent($engardeEvent);
        }


    }
}