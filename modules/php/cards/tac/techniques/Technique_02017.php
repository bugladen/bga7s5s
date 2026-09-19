<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02017 extends Technique
{
    public bool $NoTechniquesThisRound;

    /** Captured at Resolve — challenge has no duel opponent yet. */
    public int $AdversaryId;

    /**
     * True after arming until the captured adversary's next NewRound.
     * WHY: Challenge Resolve runs before the duel. The challenger's round-1
     * NewRound must NOT clear the block (01186 / 01193 premature-clear bug).
     */
    public bool $AwaitingAdversaryRound;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Adversary Cannot Activate Techniques Next Round");
        $this->NoTechniquesThisRound = false;
        $this->AdversaryId = 0;
        $this->AwaitingAdversaryRound = false;
    }

    private function clearDeferredState(Theah $theah): void
    {
        $this->NoTechniquesThisRound = false;
        $this->AdversaryId = 0;
        $this->AwaitingAdversaryRound = false;
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // Block only during the adversary's armed round (Awaiting cleared on their NewRound).
        // WHY: EventTechniqueActivated.ownerId is the duel actor character on duel choose.
        if ($event instanceof EventTechniqueActivated
            && $this->NoTechniquesThisRound
            && ! $this->AwaitingAdversaryRound
            && $this->AdversaryId != 0
            && $event->ownerId == $this->AdversaryId)
        {
            $owner = $this->getOwningCard($event->theah);
            throw new UserException(sprintf($event->theah->game->translate("%s: Techniques cannot be activated this round."), $owner->getInjectCode()));
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Arm: adversary cannot activate Techniques during their next duel round.
        // WHY: Use $event->adversaryId (CHOSEN_TARGET on challenge Resolve) — do not
        // call getDuelOpponentId / getDuelRoundOpponent; challenge has no duel yet.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $adversaryId = $event->adversaryId;
            if (! $adversaryId)
            {
                $adversaryId = (int) $event->theah->game->globals->get(Game::CHOSEN_TARGET, 0);
            }

            $this->NoTechniquesThisRound = true;
            $this->AdversaryId = $adversaryId;
            $this->AwaitingAdversaryRound = true;
            $owner = $this->getOwningCard($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearDeferredState($event->theah);
        }

        // WHY: Resolve runs before Accept/Reject. Refuse never starts a duel, so
        // clear here or the flag would leak into a later unrelated duel.
        if ($event instanceof EventChallengeRejected && $this->NoTechniquesThisRound)
        {
            $character = $this->getOwningCharacter($event->theah);
            if ($character !== null && $character->Id == $event->challengerId)
            {
                $this->clearDeferredState($event->theah);
            }
        }

        if ($event instanceof EventDuelNewRound && $this->NoTechniquesThisRound && $this->AdversaryId != 0)
        {
            // Adversary's next round begins — block is now active for this round.
            if ($event->actorId == $this->AdversaryId)
            {
                $this->AwaitingAdversaryRound = false;
                $owner = $this->getOwningCard($event->theah);
                if ($owner !== null)
                {
                    $owner->IsUpdated = true;
                }
            }
            // Owner's NewRound after the blocked adversary round ends the effect.
            // WHY: Skip while still Awaiting — after Challenge that NewRound is
            // round 1 for the challenger, before the adversary's first round.
            else
            {
                $owningCharacter = $this->getOwningCharacter($event->theah);
                if ($owningCharacter !== null
                    && $owningCharacter->Id == $event->actorId
                    && ! $this->AwaitingAdversaryRound)
                {
                    $this->clearDeferredState($event->theah);
                }
            }
        }

        if ($event instanceof EventDuelEnd && $this->NoTechniquesThisRound)
        {
            $this->clearDeferredState($event->theah);
        }
    }
}
