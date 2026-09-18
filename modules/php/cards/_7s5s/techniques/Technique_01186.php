<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01186 extends Technique
{
    public bool $CancelOpponentManeuvers;

    /** Captured at Resolve — challenge has no duel opponent yet. */
    public int $AdversaryId;

    /**
     * True after arming until the captured adversary's next NewRound.
     * WHY: Challenge Resolve runs before the duel. The challenger's round-1
     * NewRound must NOT clear the block (01193 premature-clear bug).
     */
    public bool $AwaitingAdversaryRound;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("No Adversary Maneuvers");
        $this->CancelOpponentManeuvers = false;
        $this->AdversaryId = 0;
        $this->AwaitingAdversaryRound = false;
    }

    private function clearDeferredState(Theah $theah): void
    {
        $this->CancelOpponentManeuvers = false;
        $this->AdversaryId = 0;
        $this->AwaitingAdversaryRound = false;
        $maryam = $this->getOwningCard($theah);
        if ($maryam !== null)
        {
            $maryam->IsUpdated = true;
        }
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // Arm: adversary cannot use Maneuvers during their next duel round.
        // WHY: Use $event->adversaryId (CHOSEN_TARGET on challenge Resolve) — do not
        // call getDuelOpponentId / getDuelRoundOpponent; challenge has no duel yet.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $adversaryId = $event->adversaryId;
            if (! $adversaryId)
            {
                $adversaryId = (int) $event->theah->game->globals->get(Game::CHOSEN_TARGET, 0);
            }

            $this->CancelOpponentManeuvers = true;
            $this->AdversaryId = $adversaryId;
            $this->AwaitingAdversaryRound = true;
            $maryam = $this->getOwningCard($event->theah);
            if ($maryam !== null)
            {
                $maryam->IsUpdated = true;
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearDeferredState($event->theah);
        }

        // WHY: Resolve runs before Accept/Reject. Refuse never starts a duel, so
        // clear here or the flag would leak into a later unrelated duel.
        if ($event instanceof EventChallengeRejected && $this->CancelOpponentManeuvers)
        {
            $character = $this->getOwningCharacter($event->theah);
            if ($character !== null && $character->Id == $event->challengerId)
            {
                $this->clearDeferredState($event->theah);
            }
        }

        if ($event instanceof EventDuelNewRound && $this->CancelOpponentManeuvers && $this->AdversaryId != 0)
        {
            // Adversary's next round begins — block is now active for this round.
            if ($event->actorId == $this->AdversaryId)
            {
                $this->AwaitingAdversaryRound = false;
                $maryam = $this->getOwningCard($event->theah);
                if ($maryam !== null)
                {
                    $maryam->IsUpdated = true;
                }
            }
            // Owner's NewRound after the blocked adversary round ends the effect.
            // WHY: Skip while still Awaiting — after Challenge that NewRound is
            // round 1 for the challenger, before the adversary's first round.
            else
            {
                $maryam = $this->getOwningCharacter($event->theah);
                if ($maryam !== null
                    && $maryam->Id == $event->actorId
                    && ! $this->AwaitingAdversaryRound)
                {
                    $this->clearDeferredState($event->theah);
                }
            }
        }

        if ($event instanceof EventDuelEnd && $this->CancelOpponentManeuvers)
        {
            $this->clearDeferredState($event->theah);
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // Block only during the adversary's armed round (Awaiting cleared on their NewRound).
        // EventResolveManeuver has no actorId — adversaryId is the maneuver player's opponent.
        if ($event instanceof EventResolveManeuver
            && $this->CancelOpponentManeuvers
            && ! $this->AwaitingAdversaryRound
            && $this->AdversaryId != 0)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null && $event->adversaryId == $owner->Id)
            {
                throw new UserException($event->theah->game->translate("Technique of Maryam Benu Pleroma is active. Opponent Maneuvers are prevented this round."));
            }
        }
    }
}
