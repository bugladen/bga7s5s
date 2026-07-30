<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04006 extends FactionAttachment
{
    // WHY: Flag ±1 (Benci _04001 shape), not constructor FinesseModifier — bonus is
    // duel-scoped and only while the adversary is wounded.
    public bool $AdversaryWoundedFinesseBonusApplied = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Assassin's Garb");
        $this->Image = "04006.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 6;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate("Attire"),
            clienttranslate("Mantel"),
            clienttranslate("Stealth"),
        ];

        $this->Text = clienttranslate("<p>May only equip to your <b>Duelist</b>, <b>Spy</b>, or <b>Assassin</b>.</p>
<p>During a duel, while their adversary is wounded, the equipped character gains +1[Finesse] and reveals an additional card when they gamble.</p>");

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $this->characterHasEquipTrait($character))
            {
                throw new UserException($event->theah->game->translate("Assassin's Garb can only be equipped to a Duelist, Spy, or Assassin."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $this->characterHasEquipTrait($character);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelStarted && $this->isAttached())
        {
            $this->recomputeAdversaryWoundedFinesseBonus($event->theah);
        }

        if ($event instanceof EventDuelEnd && $this->AdversaryWoundedFinesseBonusApplied)
        {
            $this->clearAdversaryWoundedFinesseBonus($event->theah);
        }

        if (($event instanceof EventChallengerSwapped || $event instanceof EventDefenderSwapped)
            && $this->isAttached())
        {
            $this->recomputeAdversaryWoundedFinesseBonus($event->theah);
        }

        if (($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
            && $this->isAttached())
        {
            $this->recomputeAdversaryWoundedFinesseBonus($event->theah, $event);
        }

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $this->recomputeAdversaryWoundedFinesseBonus($event->theah);
        }

        // WHY: EventHub clears AttachedToId before card handleEvent — use event->characterId.
        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $this->clearAdversaryWoundedFinesseBonus($event->theah, $event->characterId);
        }
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

        if ($this->isAttached()
            && $actor->Id == $this->AttachedToId
            && $this->adversaryIsWoundedInDuel($theah, $actor->Id))
        {
            $count += 1;
            $explanations[] = sprintf(
                $theah->game->translate("%s: +1 while the adversary is wounded."),
                $this->getInjectCode()
            );
        }

        return $count;
    }

    private function characterHasEquipTrait(Character $character): bool
    {
        return $character->hasTrait("Duelist")
            || $character->hasTrait("Spy")
            || $character->hasTrait("Assassin");
    }

    private function recomputeAdversaryWoundedFinesseBonus(Theah $theah, ?Event $woundOrHealEvent = null): void
    {
        $shouldHaveBonus = $this->shouldHaveAdversaryWoundedFinesseBonus($theah, $woundOrHealEvent);

        if ($shouldHaveBonus && ! $this->AdversaryWoundedFinesseBonusApplied)
        {
            $character = $this->attachedTo($theah);
            if ($character === null || $theah->game->characterIsInDiscardOrLocker($character))
            {
                return;
            }

            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $character->ControllerId,
                $character->Id,
                $character->ModifiedFinesse,
                $character->ModifiedFinesse + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($finesseEvent);

            $this->AdversaryWoundedFinesseBonusApplied = true;
            $this->IsUpdated = true;
        }
        else if (! $shouldHaveBonus && $this->AdversaryWoundedFinesseBonusApplied)
        {
            $this->clearAdversaryWoundedFinesseBonus($theah);
        }
    }

    private function clearAdversaryWoundedFinesseBonus(Theah $theah, ?int $characterId = null): void
    {
        if (! $this->AdversaryWoundedFinesseBonusApplied)
        {
            return;
        }

        $characterId = $characterId ?? $this->AttachedToId;
        $character = $theah->getCharacterById($characterId);
        if ($character !== null && ! $theah->game->characterIsInDiscardOrLocker($character))
        {
            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $character->ControllerId,
                $character->Id,
                $character->ModifiedFinesse,
                $character->ModifiedFinesse - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($finesseEvent);
        }

        $this->AdversaryWoundedFinesseBonusApplied = false;
        $this->IsUpdated = true;
    }

    private function shouldHaveAdversaryWoundedFinesseBonus(Theah $theah, ?Event $woundOrHealEvent = null): bool
    {
        if (! $this->isAttached())
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $characterId = $this->AttachedToId;
        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();
        if ($characterId != $challengerId && $characterId != $defenderId)
        {
            return false;
        }

        return $this->adversaryIsWoundedInDuel($theah, $characterId, $woundOrHealEvent);
    }

    private function adversaryIsWoundedInDuel(Theah $theah, int $participantId, ?Event $woundOrHealEvent = null): bool
    {
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $adversaryId = $theah->getDuelOpponentId($participantId);
        $adversary = $theah->getCharacterById($adversaryId);
        if ($adversary === null)
        {
            return false;
        }

        return $this->characterIsWoundedForCheck($adversary, $woundOrHealEvent);
    }

    /**
     * WHY: EventCharacterWounded/Healed run card handleEvent before/without guaranteed order.
     * If the wounded character has not handled yet, Wounds is stale — apply the event delta.
     */
    private function characterIsWoundedForCheck(Character $character, ?Event $woundOrHealEvent): bool
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

        return $wounds > 0;
    }
}
