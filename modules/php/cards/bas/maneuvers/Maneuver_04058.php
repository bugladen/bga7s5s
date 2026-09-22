<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04058 extends Maneuver implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte, Draw If En Garde");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || ! $actor->hasTrait('Sorcerer'))
        {
            return false;
        }

        // WHY: "If the adversary is engaged •" is a Maneuver cost/requirement, not a Riposte-only If.
        return $this->isAdversaryEngaged($theah, $actor->Id);
    }

    private function isAdversaryEngaged(Theah $theah, int $actorId): bool
    {
        // WHY live getCharacterById, not getDuelRoundOpponent(): last-known restores
        // Engaged from when the adversary was still in play (same as Maneuver_01084).
        $adversaryId = $theah->getDuelOpponentId($actorId);
        $adversary = $theah->getCharacterById($adversaryId);
        if ($adversary === null || $theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        return $adversary->Engaged;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate('%s adds 1 Riposte.'),
                $owner->getInjectCode()
            );
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            if ($owner === null || $actor === null)
            {
                return;
            }

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $actor->Id
            );
            $event->theah->queueEvent($sorceryStartEvent);

            if (! $actor->Engaged)
            {
                $drawEvent = EventFactory::createCardDrawnEvent(
                    $event->playerId,
                    sprintf($event->theah->game->translate('%s Maneuver effect'), $owner->getInjectCode())
                );
                $event->theah->queueEvent($drawEvent);
            }

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $actor->Id
            );
            $event->theah->queueEvent($sorceryPlayedEvent);
        }
    }
}
