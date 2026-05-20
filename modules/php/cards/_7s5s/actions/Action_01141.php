<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01141 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure Performer's Location With Combat Stat");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $theah->getCharactersInCityByPlayerId($playerId);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->globals->set(Game::PRESSURING_PLAYER, $performer->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_COMBAT);

            $pressureStats = $event->theah->getPressureStats($performer, $performer->Location, Game::STAT_COMBAT);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($performer->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $game->theah->queueEvent($pressureOccuringEvent);

            //Go straight to stHighDramaPressureLocation
            $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $performer->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            if ($event->success)
            {
                if ($event->theah->canLocationBeClaimedBy($event->playerId, $event->location))
                {
                    $claimEvent = EventFactory::createLocationClaimedEvent($event->playerId, $event->performerId, $event->location);
                    $event->theah->queueEvent($claimEvent);
                }
                else
                {
                    $event->theah->game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                        'i18n' => ['location'],
                        'location' => $event->location,
                    ]);
                }
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}