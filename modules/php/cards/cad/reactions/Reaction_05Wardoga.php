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
    // WHY: Buttons / perform run after EventCardMoved is gone.
    private int $EnemyId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Wound enemy moving to adjacent City location";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound the enemy that moved to an adjacent City location: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $enemy = $this->woundableEnemy($theah, $this->EnemyId);
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

        $this->EnemyId = $character->Id;
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
            $enemy = $this->woundableEnemy($game->theah, $this->EnemyId);
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

            $this->setUsed($game->theah, true);
        }

        $this->EnemyId = 0;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
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
