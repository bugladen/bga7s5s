<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01176 extends RiskAction
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
            return false;

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->Wounds > 0);

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->Wounds > 0));

        return $performers;
    }

    public function getActionFromHandDiscount(Theah $theah, Character $performer, CardAction $action): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action);

        if ($action->Id == $this->Id)
        {
            $performerId = $theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $theah->getCharacterById($performerId);
    
            if ($performer->hasTrait("Hero") || $performer->hasTrait("Scoundrel"))
            {
                $discount += 1;
            }
    
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);

            $owner = $this->getOwningCard($event->theah);
            $healEvent = EventFactory::createCharacterHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode());
            $event->theah->queueEvent($healEvent);
        }
    }
}