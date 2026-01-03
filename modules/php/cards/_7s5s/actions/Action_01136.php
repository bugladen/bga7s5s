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

        $performers = [];
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($characters as $character)
        {
            if ($character->Wounds == 0)
            {
                continue;
            }

            $otherCharacters = $theah->getCharactersAtLocationByPlayerId($character->Location, $playerId);
            $otherCharacters = array_filter($otherCharacters, fn($c) => $character->Id !== $c->Id);
            
            if (count($otherCharacters) == 0)
            {
                $performers[] = $character;
            }
        }

        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = [];
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($characters as $character)
        {
            if ($character->Wounds == 0)
            {
                continue;
            }

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
            $owner = $this->getOwningCard($event->theah);

            $healEvent = EventFactory::createCharacterBeingHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($healEvent);

            $game = $event->theah->game;
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);
        }
    }

}