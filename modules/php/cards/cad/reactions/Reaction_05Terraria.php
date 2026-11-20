<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Terraria extends AttachmentReaction
{
    // WHY: Public like UL 01047/03044 — nested private fields have been fragile across
    // multi-stage playerReaction serialize.
    public ?EventCharacterTargeted $characterTargetedEvent = null;
    public ?EventChallengeIssued $challengeIssuedEvent = null;
    public bool $skipNextEvent = false;
    public ?int $declinedBatchId = null;
    public int $SourceId = 0;
    public int $TargetId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Destroy this card to cancel targeting effects";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may destroy Cloud Bottle to cancel the targeting effects: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, 'Destroy and Cancel', 'cancel');
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');
        return $array;
    }

    // $initiatingPlayerId: fallback when sourceId is 0 (e.g. BasicChallenge).
    private function shouldReactToAbility(Theah $theah, int $sourceId, string $abilityId, int $initiatingPlayerId = 0): bool
    {
        $owner = $this->getOwningAttachment($theah);
        if ($owner === null)
        {
            return false;
        }

        $source = $theah->getCardById($sourceId);

        // WHY: Cancel is for opposing targeting, not your own costs/self-effects (UL 01032).
        $initiatorId = $source ? $source->ControllerId : $initiatingPlayerId;
        if ($initiatorId === 0 || $initiatorId === $owner->ControllerId)
        {
            return false;
        }

        if ($source)
        {
            $ability = $source->getAbilityById($abilityId);
            if ($ability instanceof IAbilityThatTargetsCharacters || $ability instanceof IAbilityThatTargetsCards)
            {
                return true;
            }
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action instanceof IAbilityThatTargetsCharacters || $action instanceof IAbilityThatTargetsCards)
        {
            return true;
        }

        return false;
    }

    // Hub-exit challenges never emit EventCharacterTargeted for the defender (UL 01032).
    private function challengeIsOpposingTarget(EventChallengeIssued $event): bool
    {
        if ($this->shouldReactToAbility($event->theah, $event->sourceId, $event->abilityId, $event->playerId))
        {
            return true;
        }

        $chosenAction = (string) $event->theah->game->globals->get(Game::CHOSEN_ACTION, '');
        if ($chosenAction === '' || $chosenAction === $event->abilityId)
        {
            return false;
        }

        return $this->shouldReactToAbility($event->theah, $event->sourceId, $chosenAction, $event->playerId);
    }

    private function passesCovertGate(Theah $theah, Character $owningCharacter): bool
    {
        if (! $theah->cardInCity($owningCharacter))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($owningCharacter->Location);
        return $cityLocation !== null && ! $cityLocation->isControlled();
    }

    private function getHeldEventBatchId(): ?int
    {
        foreach ([$this->characterTargetedEvent, $this->challengeIssuedEvent] as $held)
        {
            if ($held !== null && $held->batchId !== null)
            {
                return $held->batchId;
            }
        }

        return null;
    }

    private function interceptEvent(Event $event, string $property, int $sourceId, int $targetId): void
    {
        $owner = $this->getOwningAttachment($event->theah);
        if ($owner === null)
        {
            return;
        }

        if ($this->skipNextEvent)
        {
            $this->skipNextEvent = false;
            $owner->IsUpdated = true;
            return;
        }

        if ($this->declinedBatchId !== null && $event->batchId === $this->declinedBatchId)
        {
            return;
        }

        // Already holding an intercept — do not nest offers.
        if ($this->characterTargetedEvent !== null || $this->challengeIssuedEvent !== null)
        {
            return;
        }

        $clone = clone $event;
        unset($clone->theah);
        $this->$property = $clone;
        $this->SourceId = $sourceId;
        $this->TargetId = $targetId;
        $owner->IsUpdated = true;

        $event->canceled = true;

        if ($event->batchId)
        {
            $event->theah->deleteEventBatch($event->batchId);
        }

        // WHY: stackEvent + HIGH_PRIORITY — offer must pre-empt remaining MEDIUM siblings
        // of the cancelled ability batch (UL / Torres Cloak).
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $transition->priority = Event::HIGH_PRIORITY;
        $event->theah->stackEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable())
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

        if (! $this->passesCovertGate($event->theah, $owningCharacter))
        {
            return;
        }

        if ($event instanceof EventCharacterTargeted && ! $event->canceled)
        {
            if ($event->targetId != $owningCharacter->Id)
            {
                return;
            }

            if (! $this->shouldReactToAbility($event->theah, $event->sourceId, $event->abilityId, $event->playerId))
            {
                return;
            }

            $this->interceptEvent($event, 'characterTargetedEvent', $event->sourceId, $event->targetId);
            return;
        }

        if ($event instanceof EventChallengeIssued && ! $event->canceled)
        {
            // WHY: "targeted" = defender. Challenger issuing is not being targeted.
            if ($event->defenderId != $owningCharacter->Id)
            {
                return;
            }

            if (! $this->challengeIsOpposingTarget($event))
            {
                return;
            }

            $this->interceptEvent($event, 'challengeIssuedEvent', $event->sourceId, $event->defenderId);
        }
    }

    private function clearHeldEvents(Game $game, int $state = 0): void
    {
        // WHY: Mirror UL — framework challenge after target pick only aborts when
        // CHALLENGE_CANCELLED is set. Do not set the flag for unrelated targeting cancels
        // (would skip the next basic challenge in stTechniqueAvailable).
        $targeted = $this->characterTargetedEvent;
        $cancelsInFlightChallenge = $this->challengeIssuedEvent !== null;
        if (! $cancelsInFlightChallenge
            && $state === States::HIGH_DRAMA_CHALLENGE_ACTION_TARGET_CHOSEN_REACTIONS
            && $targeted !== null)
        {
            $chosenAction = (string) $game->globals->get(Game::CHOSEN_ACTION, '');
            if ($chosenAction !== '' && $targeted->abilityId === $chosenAction)
            {
                $cancelsInFlightChallenge = true;
            }
        }

        $this->characterTargetedEvent = null;
        $this->challengeIssuedEvent = null;

        if ($cancelsInFlightChallenge)
        {
            $game->globals->set(Game::CHALLENGE_CANCELLED, true);
        }
    }

    private function releaseHeldEvent(Game $game): void
    {
        if ($this->characterTargetedEvent)
        {
            $game->theah->queueEvent($this->characterTargetedEvent);
            $this->characterTargetedEvent = null;
        }

        if ($this->challengeIssuedEvent)
        {
            $game->theah->queueEvent($this->challengeIssuedEvent);
            $this->challengeIssuedEvent = null;
        }
    }

    private function resetState(?Attachment $owner): void
    {
        $this->SourceId = 0;
        $this->TargetId = 0;
        $this->declinedBatchId = null;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningAttachment($game->theah);
        $owningCharacter = $this->getOwningCharacter($game->theah);
        if ($owner === null || ! ($owningCharacter instanceof Character))
        {
            $game->gamestate->nextState('done');
            return;
        }

        if ($reactionId === 'cancel')
        {
            // WHY: Sweep remaining targeting effects / transitions before destroy so the
            // cost unequip/discard is not deleted by deleteEventsTargetingCard on the host
            // (Hexenjagd 01053 — cost wound must land after the sweep).
            if ($this->TargetId > 0)
            {
                $game->theah->deleteEventsTargetingCard($this->TargetId);
            }
            if ($this->SourceId > 0)
            {
                $game->theah->deleteTransitionEventsBySourceId($this->SourceId);
            }
            $this->clearHeldEvents($game, $state);

            $game->notify->all('message', '${reaction_inject_code}: ${player_name} destroys Cloud Bottle to cancel the targeting effects on ${character_inject_code}.', [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $owningCharacter->getInjectCode(),
            ]);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                $owner->ControllerId,
                $owningCharacter->Id,
                $owner->Id
            );
            $game->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent(
                $owner,
                $owner->Id,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $this->setUsed($game->theah, true);
            $this->resetState($owner);
            $game->gamestate->nextState('done');
            return;
        }

        if ($reactionId === 'pass')
        {
            // WHY: Capture before release nulls the held clone. skipNextEvent covers the
            // re-queued hook; declinedBatchId covers siblings from the same ability batch
            // (Amour / Giacinto — UL 01032).
            $this->declinedBatchId = $this->getHeldEventBatchId();
            $this->releaseHeldEvent($game);
            $this->skipNextEvent = true;
            $this->SourceId = 0;
            $this->TargetId = 0;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState('done');
    }
}
