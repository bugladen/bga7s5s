<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
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
    public bool $pendingLockerSend = false;
    public string $lockerCombatCardId = '';
    public string $clonedManeuverId = '';

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

            // WHY: Create a temporary clone of the maneuver on this character rather
            // than re-firing EventResolveManeuver on the original. The clone gets its
            // own unique ID (via setOwnerId) so the original isn't re-activated. The
            // clone's resolve event activates its internal state (e.g. IsActive for
            // Maneuver_02039), and because it's registered on this character via
            // addManeuver, it receives EventDuelEndOfRound through normal Card dispatch
            // and fires its delayed effect independently of the original.
            $combatCards = $event->theah->getCombatCardsForCurrentRound();
            $originalManeuver = null;
            foreach ($combatCards as $combatCard)
            {
                if ($combatCard->ControllerId == $owner->ControllerId)
                {
                    // WHY: Defer the locker send until EventDuelEndOfRound so that
                    // maneuvers with delayed effects (e.g. Maneuver_02039) can still
                    // fire their end-of-round handlers while the card is in the line.
                    $this->pendingLockerSend = true;
                    $this->lockerCombatCardId = $combatCard->Id;

                    if ($combatCard instanceof IHasManeuvers)
                    {
                        $originalManeuver = $combatCard->getManeuverById($this->lastManeuverId);
                    }
                    break;
                }
            }

            if ($originalManeuver !== null && $owner instanceof IHasManeuvers)
            {
                $clonedManeuver = new (get_class($originalManeuver))();
                $clonedManeuver->setOwnerId($owner->Id);
                $owner->addManeuver($clonedManeuver, $game, false);
                $this->clonedManeuverId = $clonedManeuver->Id;

                $adversaryId = $event->theah->getDuelOpponentId($owner->Id);
                $resolveEvent = EventFactory::createResolveManeuverEvent($owner->ControllerId, $adversaryId, $clonedManeuver->Id);
                $event->theah->eventCheck($resolveEvent);
                $event->theah->queueEvent($resolveEvent);
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->copiedRiposte = 0;
            $this->copiedParry = 0;
            $this->copiedThrust = 0;
            $this->pendingLockerSend = false;
            $this->lockerCombatCardId = '';
            $this->removeClonedManeuver($event->theah);
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEndOfRound && $this->pendingLockerSend)
        {
            $this->pendingLockerSend = false;
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $this->lockerCombatCardId);
                $event->theah->queueEvent($lockerEvent);
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

        if ($event instanceof EventDuelEnd && $this->pendingLockerSend)
        {
            $this->pendingLockerSend = false;
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $this->lockerCombatCardId);
                $event->theah->queueEvent($lockerEvent);
            }
        }

        if ($event instanceof EventDuelNewRound || $event instanceof EventDuelEnd)
        {
            $this->removeClonedManeuver($event->theah);
            $this->maneuverPerformed = false;
            $this->lastManeuverId = '';
            $this->copiedRiposte = 0;
            $this->copiedParry = 0;
            $this->copiedThrust = 0;
            $this->pendingLockerSend = false;
            $this->lockerCombatCardId = '';

            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }
    }

    private function removeClonedManeuver(Theah $theah): void
    {
        if ($this->clonedManeuverId === '')
        {
            return;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null && $owner instanceof IHasManeuvers)
        {
            $maneuver = $owner->getManeuverById($this->clonedManeuverId);
            if ($maneuver !== null)
            {
                $owner->removeManeuver($maneuver, $theah->game, false);
            }
        }
        $this->clonedManeuverId = '';
    }
}
