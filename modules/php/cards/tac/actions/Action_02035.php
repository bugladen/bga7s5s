<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02035 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Scoundrel Action: Finesse Pressure; collect Renown');
        $this->RequiresPerformerSelected = true;
    }

    /**
     * Opposed Scoundrels in the city whose location has Renown to collect on success.
     *
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_filter($performers, fn($c) => $c->hasTrait("Scoundrel"));
        $performers = array_filter($performers, function ($c) use ($theah) {
            $loc = $theah->getCityLocation($c->Location);
            return $loc !== null && $loc->Renown > 0;
        });

        return array_values($performers);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CASTILLIAN_CAPER_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_FINESSE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);

            $this->announceAction($game);
            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($game);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id) {
            if ($event->success) {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($event->theah);
                if ($game->getRenownForLocation($event->location) > 0) {
                    $remove = EventFactory::createReknownRemovedFromLocationEvent(
                        $event->playerId,
                        $event->location,
                        1,
                        $owner->getInjectCode()
                    );
                    $event->theah->queueEvent($remove);
                    $gain = EventFactory::createPlayerGainsReknownEvent($event->playerId, 1);
                    $event->theah->queueEvent($gain);
                }
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
