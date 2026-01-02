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

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $characters = $theah->getCharactersInPlay();
        $characters = array_filter($characters, fn($character) => $character->Wounds > 0);

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInPlay();
        $characters = array_values(array_filter($characters, fn($performer) => $performer->Wounds > 0));

        return $characters;
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, Array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        if ($action->Id == $this->Id)
        {
            $performerId = $theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $theah->getCharacterById($performerId);
    
            if ($performer->hasTrait("Hero") || $performer->hasTrait("Scoundrel"))
            {
                $discount += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Target is a Hero or Scoundrel."), $owner->getInjectCode());
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
            $healEvent = EventFactory::createCharacterBeingHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($healEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}