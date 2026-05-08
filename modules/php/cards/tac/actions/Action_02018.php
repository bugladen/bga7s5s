<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02018 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound all Characters at your Performer's Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => 
            $theah->cardInCity($character) && $character->hasTrait("Zealot"));
        return count($characters) > 0;
    }


    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($performer) => 
            $theah->cardInCity($performer) && $performer->hasTrait("Zealot")));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $characters = $event->theah->getCharactersAtLocation($performer->Location);
            foreach ($characters as $character)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}