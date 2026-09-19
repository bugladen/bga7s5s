<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01193 extends Technique
{
    public bool $ReduceAdversaryThrust;

    /** Captured at Resolve — challenge has no duel opponent yet. */
    public int $AdversaryId;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-1 Thrust to Adversary");
        $this->ReduceAdversaryThrust = false;
        $this->AdversaryId = 0;
    }

    /**
     * WHY: Dame of Swords (02055) / I Know That Trick (01165) clone this technique
     * onto the participant Character. The original lives on Burnished Cuirass and
     * must stay attached; Character owners are valid copies and have no Attachment.
     */
    private function ownerCanApplyDeferredEffect(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        if ($owner instanceof Attachment)
        {
            return $owner->isAttached();
        }

        return $owner instanceof Character;
    }

    private function markOwnerUpdated(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function clearDeferredState(Theah $theah): void
    {
        $this->ReduceAdversaryThrust = false;
        $this->AdversaryId = 0;
        $this->markOwnerUpdated($theah);
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // Arm deferred -1 Thrust for the adversary's next combat card.
        // WHY: Use $event->adversaryId (CHOSEN_TARGET on challenge Resolve) — do not
        // call getDuelOpponentId / getDuelRoundOpponent; challenge has no duel yet.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $adversaryId = $event->adversaryId;
            if (! $adversaryId)
            {
                $adversaryId = (int) $event->theah->game->globals->get(Game::CHOSEN_TARGET, 0);
            }

            $this->ReduceAdversaryThrust = true;
            $this->AdversaryId = $adversaryId;
            $this->markOwnerUpdated($event->theah);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearDeferredState($event->theah);
        }

        // WHY: Resolve runs before Accept/Reject. Refuse never starts a duel, so
        // clear here or the flag would leak into a later unrelated duel.
        if ($event instanceof EventChallengeRejected && $this->ReduceAdversaryThrust)
        {
            $character = $this->getOwningCharacter($event->theah);
            if ($character !== null && $character->Id == $event->challengerId)
            {
                $this->clearDeferredState($event->theah);
            }
        }

        // Apply -1 Thrust when the captured adversary's combat card is calculated.
        // WHY: Match actorId to stored AdversaryId (not "owner == event.adversaryId")
        // so challenge-armed activations survive the challenger's first NewRound.
        // EventDuelNewRound must NOT clear when the owner becomes actor — after a
        // Challenge that NewRound is round 1 for the challenger, before the
        // adversary's first combat card (see Maneuver_01084: clear on apply/end only).
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->ReduceAdversaryThrust)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah) && $this->AdversaryId != 0)
            {
                $owner = $this->getOwningCard($event->theah);
                if ($event->actorId == $this->AdversaryId)
                {
                    $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the Adversary's Thrust by %d"), $owner->getInjectCode(), 1);
                    $event->removeThrust(1);
                    $this->clearDeferredState($event->theah);
                }
            }
        }

        if ($event instanceof EventDuelEnd && $this->ReduceAdversaryThrust)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $this->clearDeferredState($event->theah);
            }
        }
    }
}
