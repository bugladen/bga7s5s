<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01132 extends RiskCityAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move All Engaged Characters Home, Engage Remaining Characters");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $sorcerers = array_values(array_filter($characters, fn($character) => $character->hasTrait("Sorcerer")));
        return count($sorcerers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($character) => $character->hasTrait("Sorcerer")));
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

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
            $event->theah->queueEvent($sorceryStartEvent);

            $characters = $event->theah->getCharactersAtLocation($performer->Location);
            $engagedCharacters = array_values(array_filter($characters, fn($character) => $character->Engaged));
            foreach ($engagedCharacters as $character)
            {
                $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
                $event->theah->queueEvent($moveEvent);
            }

            $remainingCharacters = array_values(array_filter($characters, fn($character) => ! $character->Engaged));
            foreach ($remainingCharacters as $character)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
                $event->theah->queueEvent($engageEvent);
            }

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
            $event->theah->queueEvent($sorceryPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }




}