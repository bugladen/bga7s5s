<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02043a extends Technique
{
    public bool $maneuverPerformed = false;
    public string $lastManeuverId = '';
    public int $copiedRiposte = 0;
    public int $copiedParry = 0;
    public int $copiedThrust = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Copy Maneuver effects, send combat card to The Locker");
        $this->ResetOnDuelEnd = false;
        $this->ResetOnDayEnd = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        if (! $this->maneuverPerformed)
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || $actor->Id != $owner->Id)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null)
            {
                return;
            }

            $actor = $event->theah->getDuelRoundActor();
            if ($actor !== null && $actor->Id == $owner->Id)
            {
                $this->maneuverPerformed = true;
                $this->lastManeuverId = $event->maneuverId;
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null)
            {
                return;
            }

            $game = $event->theah->game;

            // Read the maneuver's combat values from the DB before re-firing resolve
            $duelId = $game->globals->get(Game::DUEL_ID);
            $round = $game->globals->get(Game::DUEL_ROUND);
            $sql = "SELECT COALESCE(maneuver_riposte, 0) as maneuver_riposte, COALESCE(maneuver_parry, 0) as maneuver_parry, COALESCE(maneuver_thrust, 0) as maneuver_thrust FROM duel_round WHERE duel_id = $duelId AND round = $round";
            $result = $game->getObjectFromDB($sql);
            $this->copiedRiposte = (int)$result['maneuver_riposte'];
            $this->copiedParry = (int)$result['maneuver_parry'];
            $this->copiedThrust = (int)$result['maneuver_thrust'];
            $owner->IsUpdated = true;

            // Re-fire EventResolveManeuver for special effects and DB logging
            $adversaryId = $event->theah->getDuelOpponentId($owner->Id);
            $resolveEvent = EventFactory::createResolveManeuverEvent($owner->ControllerId, $adversaryId, $this->lastManeuverId);
            $event->theah->eventCheck($resolveEvent);
            $event->theah->queueEvent($resolveEvent);

            // Send the combat card to The Locker
            $combatCards = $event->theah->getCombatCardsForCurrentRound();
            foreach ($combatCards as $combatCard)
            {
                if ($combatCard->ControllerId == $owner->ControllerId)
                {
                    $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $combatCard->Id);
                    $event->theah->queueEvent($lockerEvent);
                    break;
                }
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->copiedRiposte = 0;
            $this->copiedParry = 0;
            $this->copiedThrust = 0;
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        // Add the copied maneuver values through the technique channel
        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $event->riposte += $this->copiedRiposte;
            $event->parry += $this->copiedParry;
            $event->thrust += $this->copiedThrust;

            if ($this->copiedRiposte > 0 || $this->copiedParry > 0 || $this->copiedThrust > 0)
            {
                $owner = $this->getOwningCharacter($event->theah);
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('Technique [%s]: Copied maneuver effects.'),
                    $event->theah->game->translate($this->Name)
                );
            }
        }

        if ($event instanceof EventDuelNewRound || $event instanceof EventDuelEnd)
        {
            $this->maneuverPerformed = false;
            $this->lastManeuverId = '';
            $this->copiedRiposte = 0;
            $this->copiedParry = 0;
            $this->copiedThrust = 0;

            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }
    }
}
