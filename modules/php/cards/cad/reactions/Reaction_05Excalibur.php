<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Excalibur extends AttachmentReaction
{
    // WHY: Buttons / perform run after the issued event is gone. Stash performer only —
    // wound targets are recomputed at button/perform time (any opposing at location).
    private int $PerformerId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Wound performer and opposing character of equal or greater Combat";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound the performer and an opposing character of equal or greater Combat: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        foreach ($this->getValidWoundTargets($theah) as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, 'Wound ' . $character->Name, "wound-{$character->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventChallengeIssued) || $event->canceled || ! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        $performer = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! ($performer instanceof Character))
        {
            return;
        }

        // WHY: "When the equipped performer issues a challenge" — host must be the challenger.
        if ($event->challengerId !== $performer->Id)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($performer))
        {
            return;
        }

        $this->PerformerId = $performer->Id;

        // WHY: "target opposing character with equal or greater [Combat]" — any opposing
        // character at the performer's location, not only the challenge defender.
        if (count($this->getValidWoundTargets($event->theah)) === 0)
        {
            $this->PerformerId = 0;
            return;
        }

        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if (str_starts_with($reactionId, 'wound-'))
        {
            $owner = $this->getOwningAttachment($game->theah);
            $performer = $game->theah->getCharacterById($this->PerformerId);
            $targetId = (int) substr($reactionId, strlen('wound-'));
            $target = $this->validWoundTarget($game->theah, $targetId);

            if ($owner === null
                || ! ($performer instanceof Character)
                || $target === null
                || $game->characterIsInDiscardOrLocker($performer))
            {
                throw new UserException("Choose an opposing character of equal or greater Combat at this location");
            }

            $game->notify->all("message", '${owner_inject_code}: ${player_name} wounds ${performer_inject_code} and ${target_inject_code}.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "performer_inject_code" => $performer->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $woundPerformer = EventFactory::createCharacterBeingWoundedEvent(
                $performer->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundPerformer);

            $woundTarget = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundTarget);

            $this->setUsed($game->theah, true);
            $this->PerformerId = 0;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }

    /**
     * Opposing characters at the performer's location with ModifiedCombat >= performer.
     *
     * @return Character[]
     */
    private function getValidWoundTargets(Theah $theah): array
    {
        $performer = $theah->getCharacterById($this->PerformerId);
        if (! ($performer instanceof Character) || $theah->game->characterIsInDiscardOrLocker($performer))
        {
            return [];
        }

        $targets = [];
        foreach ($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId) as $character)
        {
            if (! ($character instanceof Character))
            {
                continue;
            }

            // WHY: Printed [Combat] = current modified value (Pattern A spaulders/shield).
            if ($character->ModifiedCombat < $performer->ModifiedCombat)
            {
                continue;
            }

            if ($theah->game->characterIsInDiscardOrLocker($character))
            {
                continue;
            }

            $targets[] = $character;
        }

        return $targets;
    }

    private function validWoundTarget(Theah $theah, int $characterId): ?Character
    {
        foreach ($this->getValidWoundTargets($theah) as $character)
        {
            if ($character->Id === $characterId)
            {
                return $character;
            }
        }

        return null;
    }
}
