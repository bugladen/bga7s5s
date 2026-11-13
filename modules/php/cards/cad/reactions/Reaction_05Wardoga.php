<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Wardoga extends AttachmentReaction
{
    // WHY: Blood Mark (and any multi-move) queues several EventCardMoved before the first
    // reaction UI runs. A single EnemyId was overwritten by the second mover, then cleared
    // on Pass — so the second prompt only had Pass. FIFO like Reaction_01182.
    public array $EnemyIds = [];

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Wound enemy moving to adjacent City location";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: Name the current mover so dual arrivals (Blood Mark) are distinguishable.
        $enemy = $this->currentEnemy($theah);
        $who = $enemy !== null ? $enemy->Name : 'the enemy';

        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound ' . $who . ' that moved to an adjacent City location: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $enemy = $this->currentEnemy($theah);
        if ($enemy !== null)
        {
            $array[] = $this->createButtonProperty($theah->game, 'Wound ' . $enemy->Name, 'wound');
        }
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved) || ! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        $owningCharacter = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! ($owningCharacter instanceof Character))
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owningCharacter))
        {
            return;
        }

        // WHY: Covert Reaction — only at uncontrolled City locations (Seadog / Valeri / Robin).
        if (! $event->theah->cardInCity($owningCharacter))
        {
            return;
        }

        $cityLocation = $event->theah->getCityLocation($owningCharacter->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return;
        }

        $character = $event->theah->getCardById($event->cardId);
        if (! ($character instanceof Character))
        {
            return;
        }

        // WHY: "enemy" = different controller. Not "opposing" — no fromLocation same-site gate.
        if ($character->ControllerId == $owningCharacter->ControllerId)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($character))
        {
            return;
        }

        if (! $event->theah->locationInCity($event->toLocation))
        {
            return;
        }

        $adjacent = $event->theah->getAdjacentCityLocations($owningCharacter->Location, false);
        if (! in_array($event->toLocation, $adjacent, true))
        {
            return;
        }

        $this->EnemyIds[] = $character->Id;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningAttachment($game->theah);

        if ($reactionId === 'wound')
        {
            $enemyId = array_shift($this->EnemyIds);
            // WHY: Used spends the reaction — drop remaining movers and cancel their queued UIs
            // (same as Reaction_01182) so dead Pass-only prompts do not appear.
            $this->EnemyIds = [];
            $enemy = $this->woundableEnemy($game->theah, (int) $enemyId);
            if ($owner === null || $enemy === null)
            {
                throw new UserException("Choose the enemy that moved to an adjacent City location");
            }

            $game->notify->all("message", '${owner_inject_code}: ${player_name} wounds ${enemy_inject_code}.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "enemy_inject_code" => $enemy->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $enemy->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $game->theah->deleteTransitionEvents($this->Id);
            $this->setUsed($game->theah, true);
        }

        if ($reactionId === 'pass')
        {
            // WHY: Pass only declines this arrival; later movers in the FIFO still get Wound.
            array_shift($this->EnemyIds);
        }

        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }

    private function currentEnemy(Theah $theah): ?Character
    {
        if ($this->EnemyIds === [])
        {
            return null;
        }

        return $this->woundableEnemy($theah, (int) $this->EnemyIds[0]);
    }

    private function woundableEnemy(Theah $theah, int $characterId): ?Character
    {
        if ($characterId <= 0)
        {
            return null;
        }

        $character = $theah->getCharacterById($characterId);
        if ($character === null || $theah->game->characterIsInDiscardOrLocker($character))
        {
            return null;
        }

        return $character;
    }
}
