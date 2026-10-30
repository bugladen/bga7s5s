<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _03cd21 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Silver Spine');
        $this->Image = '03cd21.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;
        $this->CityCardNumber = 21;

        $this->WealthCost = 2;
        $this->ResolveModifier = 1;

        $this->InPlayXImageOffset = -20;

        $this->Traits = [
            clienttranslate('Artifact'),
            clienttranslate('Syrneth'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<b>Forced:</b> Each Day, the first time an opponent's Risk targets the equipped character • Cancel the effects.");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        // WHY: gate on isAttached() because the cancel only fires while the artifact
        // is equipped to a character. Source-id checks mirror _01186 (Maryam) — the
        // event must have originated from a card (sourceId != 0) and that card must
        // be a Risk that targets characters, controlled by someone other than the
        // equipped character's controller ("an opponent's Risk").

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            (($event instanceof EventCardMoving && $event->cardId == $this->AttachedToId && $event->sourceId != 0) ||
            ($event instanceof EventCardEngaged && $event->cardId == $this->AttachedToId && $event->sourceId != 0))
        )
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);
                $event->canceled = true;
                return;
            }
        }

        // WHY: Defending Honor's printed target is the enemy who then issues the
        // challenge, so they are the challenger, not the defender. defenderId-only
        // misses that Risk. The targeting event is the moment the Forced must fire.
        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventCharacterTargeted && $event->targetId == $this->AttachedToId && $event->sourceId != 0)
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->cancelRiskEffects($event, (int)$event->sourceId, true);
                return;
            }
        }

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventChallengeIssued && $event->sourceId != 0
            && ($event->defenderId == $this->AttachedToId || $event->challengerId == $this->AttachedToId))
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);
                // WHY: EventHub skips ChallengeIssued side-effects when canceled, but the
                // challenge state machine only aborts if CHALLENGE_CANCELLED is set
                // (stChallengeActionCheckCancelled). Same signal as 01088 / 01032 / Vittoria.
                // challengerId covers Defending Honor if the choose-target transition
                // already ran (the targeted character is the one issuing the challenge).
                $event->theah->game->globals->set(Game::CHALLENGE_CANCELLED, true);
                $event->canceled = true;
                return;
            }
        }

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventCharacterBeingWounded && $event->characterId == $this->AttachedToId && $event->sourceId != 0)
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);
                $event->canceled = true;
                return;
            }
        }

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventAttachmentEquipping && $event->characterId == $this->AttachedToId && $event->sourceId != 0)
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);

                // WHY discard the would-be attachment: EventAttachmentEquipping was canceled,
                // so EventAttachmentEquipped never fires to place the card. Without this the
                // attachment is left in limbo. Same shape as _01186's handler — route via
                // createAttachmentDiscardedFromPlayEvent (city vs faction discard).
                $attachment = $event->theah->getCardById($event->attachmentId);
                if ($attachment instanceof Attachment)
                {
                    $removedEvent = EventFactory::createCardRemovedFromPlayEvent($event->playerId, $attachment->Id, $attachment->Location);
                    $event->theah->queueEvent($removedEvent);

                    $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent(
                        $attachment,
                        $sourceId = 0,
                        $asEffect = false,
                        $fromLocation = null,
                        $event->playerId
                    );
                    $event->queueEvent($discardEvent);
                }

                $event->canceled = true;
                return;
            }
        }

        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay && $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED))
        {
            $this->removeCondition(Game::SILVER_SPINE_ABILITY_USED);
            $event->theah->game->notify->all("silverSpineAbilityRemoved", "", [
                "cardId" => $this->Id,
            ]);
        }
    }

    private function isOpponentRiskTargetingCharacters(Event $event, int $sourceId): bool
    {
        $source = $event->theah->getCardById($sourceId);
        // WHY: Celerity / Unsanctioned / Rage print "These effects cannot be cancelled."
        // Night of Drinking already gates on Risk::effectsCannotBeCancelled(); Forced
        // cancel must too — otherwise Spine announces and spends its Day while the
        // challenge still proceeds (the printed immunity wins).
        return $source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters
            && $source->ControllerId != $this->ControllerId
            && ! $source->effectsCannotBeCancelled();
    }

    private function markAbilityUsed(Game $game): void
    {
        $this->addCondition(Game::SILVER_SPINE_ABILITY_USED);
        $game->notify->all("silverSpineAbilityUsed", clienttranslate('${card_inject_code} cancels the effects of the opposing Risk.'), [
            "cardId" => $this->Id,
            "card_inject_code" => $this->getInjectCode(),
        ]);
    }

    // WHY: Forced, not a Reaction — no Decline. The first opponent Risk that
    // targets the equipped character each Day cancels here with no player choice.
    // $abortUnbatchedFollowUp is for Defending Honor. The picker transition is
    // emitted only when the targeting event survives, and it shares that batch.
    // deleteEventBatch strips it when this handler runs second. Always resolve
    // the Risk too: a batch-only cancel left the action with no resolved event.
    private function cancelRiskEffects(Event $event, int $sourceId, bool $abortUnbatchedFollowUp): void
    {
        $this->markAbilityUsed($event->theah->game);
        $game = $event->theah->game;

        if ($event->batchId)
        {
            $event->theah->deleteEventBatch($event->batchId);
        }

        // WHY: Framework challenge target-pick only queues CharacterTargeted, then
        // endOfEvents always enters TECHNIQUE_AVAILABLE. Abort only via
        // CHALLENGE_CANCELLED (UL / Blood Like Winter). Challenge types other than
        // NORMAL are already set before the picker (Censure, Celerity, …).
        // Defending Honor is also non-NORMAL but lives outside that hub — cancelling
        // leaves no "01078" transition and never hits stTechniqueAvailable, so it
        // still needs ActionResolved below. skipChallengeBeforeTechnique resolves
        // the framework path — do not double-queue ActionResolved here.
        $challengeType = (int) $game->globals->get(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
        $inFrameworkChallengePick = $challengeType !== Game::NORMAL_CHALLENGE_TYPE
            && $challengeType !== Game::DEFENDING_HONOR_CHALLENGE_TYPE;

        if ($inFrameworkChallengePick)
        {
            $game->globals->set(Game::CHALLENGE_CANCELLED, true);
        }
        elseif ($abortUnbatchedFollowUp)
        {
            // WHY not elseif on batch alone: Defending Honor batches the targeting
            // event and only emits "01078" when that event survives. A batch delete
            // removes the picker if this handler already queued it, and the action
            // still has to resolve — otherwise the Risk ends with no challenge.
            $event->theah->deleteTransitionEventsBySourceId($sourceId);
            $source = $event->theah->getCardById($sourceId);
            $playerId = ($source && $source->ControllerId) ? (int)$source->ControllerId : (int)$event->playerId;
            $resolved = EventFactory::createActionResolvedEvent($playerId);
            $event->theah->queueEvent($resolved);
        }

        $event->canceled = true;
    }
}
