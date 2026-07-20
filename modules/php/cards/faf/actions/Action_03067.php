<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03067 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Leader, Pressure Location, Claim if Successful");
        // WHY: Leader City Action — Leader is the only legal performer; no chooser.
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $leader = $theah->getLeaderByPlayerId($playerId);
        if ($leader === null || ! $theah->cardInCity($leader))
        {
            return false;
        }

        if (! $this->controlsFewerLocationsThanAnOpponent($theah, $playerId))
        {
            return false;
        }

        return count($this->getPressureableStats($leader)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $leader = $event->theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader === null)
            {
                return;
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $leader->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03067", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->performerId
                ? $event->theah->getCharacterById($event->performerId)
                : null;

            if ($event->success && $performer !== null)
            {
                if ($event->theah->canLocationBeClaimedBy($event->playerId, $event->location))
                {
                    $claimEvent = EventFactory::createLocationClaimedEvent(
                        $event->playerId,
                        $performer->Id,
                        $event->location
                    );
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

            // WHY: Unique spend — locker whether pressure succeeds or fails.
            $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $owner->Id);
            $event->theah->queueEvent($lockerEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03067)
        {
            $owner = $this->getOwningCard($game->theah);
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);

            $args['performerId'] = $leader?->Id;
            $args['stats'] = $leader !== null ? $this->getPressureableStats($leader) : [];
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03067)
        {
            $owner = $this->getOwningCard($game->theah);
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader === null)
            {
                throw new UserException($game->translate("You no longer have a Leader in play."));
            }

            $stat = $this->statFromChoiceId($id);
            if ($stat === null || ! $leader->canPressure($stat))
            {
                throw new UserException($game->translate("Choose Combat, Finesse, or Influence."));
            }

            if (! $this->controlsFewerLocationsThanAnOpponent($game->theah, $owner->ControllerId))
            {
                throw new UserException($game->translate("You must control fewer locations than an opponent."));
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);
            $game->globals->set(Game::PRESSURING_PLAYER, $leader->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, $stat);

            $pressureStats = $game->theah->getPressureStats($leader, $leader->Location, $stat);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent(
                $leader->ControllerId,
                $leader->Id,
                $leader->Location,
                $pressureStats
            );
            $game->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent(
                $leader->ControllerId,
                $owner->Id,
                "pressureLocation",
                $this->Id
            );
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState("statChosen");
        }
    }

    /**
     * @return list<string>
     */
    private function getPressureableStats(Character $leader): array
    {
        $stats = [];
        foreach ([Game::STAT_COMBAT, Game::STAT_FINESSE, Game::STAT_INFLUENCE] as $stat)
        {
            if ($leader->canPressure($stat))
            {
                $stats[] = $stat;
            }
        }

        return $stats;
    }

    private function controlsFewerLocationsThanAnOpponent(Theah $theah, int $playerId): bool
    {
        $myCount = $this->countControlledLocations($theah, $playerId);
        $players = $theah->game->loadPlayersBasicInfos();

        foreach ($players as $opponentId => $info)
        {
            if ((int)$opponentId === $playerId)
            {
                continue;
            }

            if ($myCount < $this->countControlledLocations($theah, (int)$opponentId))
            {
                return true;
            }
        }

        return false;
    }

    private function countControlledLocations(Theah $theah, int $playerId): int
    {
        $count = 0;
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Controller == $playerId)
            {
                $count++;
            }
        }

        return $count;
    }

    private function statFromChoiceId(int $id): ?string
    {
        return match ($id)
        {
            1 => Game::STAT_COMBAT,
            2 => Game::STAT_FINESSE,
            3 => Game::STAT_INFLUENCE,
            default => null,
        };
    }
}
