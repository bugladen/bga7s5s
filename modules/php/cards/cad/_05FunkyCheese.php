<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05FunkyCheese;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05FunkyCheese extends Character implements IHasReactions
{
    use ReactionTrait;

    // WHY: Ise flag — attachments also mutate Influence; only ±1 on transition.
    public bool $LeaderWoundInfluenceBonusApplied = false;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Geistliche Leichenberg";
        $this->Title = "Got to Hell!";
        $this->Image = "05FunkyCheese.v4.3.jpg";
        $this->InPlayXImageOffset = 20;
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 14;

        $this->initializeFaction("Eisen");

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            "Villain",
            "Zealot",
            "Academic",
            "Priest",
            "Eisen"
        ];

        $this->Text = "<p>While your <b>Leader</b> has more wounds than Leichenberg, he has +1 [Influence].</p>
<p><b>Covert Reaction:</b> When a pressure occurs at this location • Wound Leichenberg and target opposing non-Leader.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05FunkyCheese(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($this->abilitiesAreBlanked())
        {
            return;
        }

        // WHY: Bonus compares Leader vs Leichenberg wounds — recompute when either changes.
        if ($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
        {
            $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);
            if ($event->characterId == $this->Id
                || ($leader !== null && $event->characterId == $leader->Id))
            {
                $this->recomputeLeaderWoundInfluenceBonus($event->theah, $event);
            }
        }

        // WHY: Enter play against an already-wounded Leader — Ise only hooks wound/heal on self.
        if (($event instanceof EventCharacterMustered || $event instanceof EventApproachCharacterPlayed)
            && $event->characterId == $this->Id)
        {
            $this->recomputeLeaderWoundInfluenceBonus($event->theah);
        }
    }

    private function recomputeLeaderWoundInfluenceBonus(Theah $theah, ?Event $woundOrHealEvent = null): void
    {
        if ($this->ControllerId == 0)
        {
            return;
        }
        if ($theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }
        if ($this->IsDying)
        {
            return;
        }

        $shouldHaveBonus = $this->leaderHasMoreWoundsThanSelf($theah, $woundOrHealEvent);

        if ($shouldHaveBonus && ! $this->LeaderWoundInfluenceBonusApplied)
        {
            $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedInfluence,
                $this->ModifiedInfluence + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($influenceEvent);

            $this->LeaderWoundInfluenceBonusApplied = true;
            $this->IsUpdated = true;
        }
        else if (! $shouldHaveBonus && $this->LeaderWoundInfluenceBonusApplied)
        {
            $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedInfluence,
                $this->ModifiedInfluence - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($influenceEvent);

            $this->LeaderWoundInfluenceBonusApplied = false;
            $this->IsUpdated = true;
        }
    }

    private function leaderHasMoreWoundsThanSelf(Theah $theah, ?Event $woundOrHealEvent = null): bool
    {
        $leader = $theah->getLeaderByPlayerId($this->ControllerId);
        if (! ($leader instanceof Leader) || $theah->game->characterIsInDiscardOrLocker($leader))
        {
            return false;
        }

        $leaderWounds = $this->woundsAccountingForPendingEvent($leader, $woundOrHealEvent);
        $myWounds = $this->woundsAccountingForPendingEvent($this, $woundOrHealEvent);

        return $leaderWounds > $myWounds;
    }

    /**
     * WHY: EventCharacterWounded/Healed run card handleEvent without guaranteed order.
     * If the wounded character has not handled yet, Wounds is stale — apply the event delta (Benci).
     */
    private function woundsAccountingForPendingEvent(Character $character, ?Event $woundOrHealEvent): int
    {
        $wounds = $character->Wounds;

        if ($woundOrHealEvent instanceof EventCharacterWounded
            && $woundOrHealEvent->characterId == $character->Id
            && ! $woundOrHealEvent->characterHandled)
        {
            $wounds += $woundOrHealEvent->wounds;
        }
        else if ($woundOrHealEvent instanceof EventCharacterHealed
            && $woundOrHealEvent->characterId == $character->Id
            && ! $woundOrHealEvent->characterHandled)
        {
            $wounds -= $woundOrHealEvent->wounds;
            if ($wounds < 0)
            {
                $wounds = 0;
            }
        }

        return $wounds;
    }

    // WHY: Fate's Silence skips handleEvent — +1 Influence would stick while blanked.
    public function onAbilitiesBlanked(Theah $theah): void
    {
        if (! $this->LeaderWoundInfluenceBonusApplied || $this->ControllerId == 0)
        {
            return;
        }

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $this->Id,
            $this->ModifiedInfluence,
            $this->ModifiedInfluence - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);

        $this->LeaderWoundInfluenceBonusApplied = false;
        $this->IsUpdated = true;
    }

    public function onAbilitiesUnblanked(Theah $theah): void
    {
        if ($this->IsDying || $theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }
        $this->recomputeLeaderWoundInfluenceBonus($theah);
    }
}
