<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Glass extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Pressure Performer's Location With Finesse; Unclaim if Successful";
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
        // WHY: RiskCityAction already restricts to city characters; Unseat further needs
        // canPressure(Finesse) (excludes dashed Finesse). Same shape as Strong Hands Combat.
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            fn($character) => $character->canPressure(Game::STAT_FINESSE)
        ));
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
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);

            $pressureStats = $event->theah->getPressureStats($performer, $performer->Location, Game::STAT_FINESSE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $pressureStats
            );
            $game->theah->queueEvent($pressureOccuringEvent);

            // Go straight to stHighDramaPressureLocation
            $transitionEvent = EventFactory::createTransitionEvent(
                $performer->ControllerId,
                $performer->Id,
                "pressureLocation",
                $this->Id
            );
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            if ($event->success)
            {
                // WHY: Unclaim only when controlled. canLocationBecomeUncontrolledBy does not
                // require a current controller — check explicitly so already-uncontrolled
                // locations skip silently (printed "becomes uncontrolled"). Mirror C.10 / 04048b.
                if ($event->theah->game->getControllerForLocation($event->location) != 0)
                {
                    if ($event->theah->canLocationBecomeUncontrolledBy($event->playerId, $event->location))
                    {
                        $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent(
                            $event->playerId,
                            $event->location
                        );
                        $event->theah->queueEvent($uncontrolledEvent);
                    }
                    else
                    {
                        // WHY: Emit-only Indomitable Will / similar — pressure still resolved.
                        $event->theah->game->notify->all("message", '${location} cannot become uncontrolled.', [
                            'i18n' => ['location'],
                            'location' => $event->location,
                        ]);
                    }
                }
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
