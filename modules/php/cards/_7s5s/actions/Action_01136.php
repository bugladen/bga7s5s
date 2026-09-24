<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01136 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal a Wound if Alone at Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = [];
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($characters as $character)
        {
            // WHY: Wounds are not a cost — alone-at-location is the printed If gate.
            // Heal is the effect; unwounded alone performers may still play.
            $otherCharacters = $theah->getCharactersAtLocationByPlayerId($character->Location, $playerId);
            $otherCharacters = array_filter($otherCharacters, fn($c) => $character->Id !== $c->Id);

            if (count($otherCharacters) == 0)
            {
                $performers[] = $character;
            }
        }

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($event->theah);

            // WHY: Heal is the effect, not a cost — no-op when already at 0 wounds.
            if ($performer !== null && $performer->Wounds > 0)
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($healEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
