<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04001;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04001 extends Character
{
    // WHY: Flag ±1 (Ise shape), not absolute Combat+bonus — attachments also mutate ModifiedCombat.
    public bool $OpposedWoundedCombatBonusApplied = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Benci Bommarito");
        $this->Title = clienttranslate("Favored Nephew");
        $this->Image = "04001.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 1;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Red Hand"),
            clienttranslate("Protégé"),
            clienttranslate("Vodacce")
        ];

        $this->Text = clienttranslate("<p>While Benci is opposed by two or more wounded characters, he gains +1[Combat].</p>
<p><b>Technique:</b> Look at the top two cards of your deck. You may sink one or both and return the others in any order.
<br><i>(Techniques do not resolve if the challenge is refused.)</i></p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_04001(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->recomputeOpposedWoundedCombatBonus($event->theah, $event->toLocation, $event);
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id
            && ($event->toLocation == $this->Location || $event->fromLocation == $this->Location))
        {
            $this->recomputeOpposedWoundedCombatBonus($event->theah, $this->Location, $event);
        }

        if ($event instanceof EventCharacterMustered
            && ($event->characterId == $this->Id || $event->location == $this->Location))
        {
            $this->recomputeOpposedWoundedCombatBonus($event->theah, $event->location);
        }

        if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
        {
            // WHY: Own approach — ControllerId may not be on the in-memory card yet.
            $this->recomputeOpposedWoundedCombatBonus($event->theah, Game::LOCATION_PLAYER_HOME, null, $event->playerId);
        }

        if ($event instanceof EventApproachCharacterPlayed
            && $event->characterId != $this->Id
            && $this->Location == Game::LOCATION_PLAYER_HOME
            && $event->playerId == $this->ControllerId)
        {
            $this->recomputeOpposedWoundedCombatBonus($event->theah, Game::LOCATION_PLAYER_HOME);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            // WHY: runEventHubAfterCards — Location still pre-locker during card handleEvent.
            if ($character !== null && $character->Location == $this->Location)
            {
                $this->recomputeOpposedWoundedCombatBonus($event->theah, $this->Location);
            }
        }

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null && $character->Location == $this->Location)
            {
                $this->recomputeOpposedWoundedCombatBonus($event->theah, $this->Location);
            }
        }

        if ($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null
                && ($event->characterId == $this->Id || $character->Location == $this->Location))
            {
                $this->recomputeOpposedWoundedCombatBonus($event->theah, $this->Location, null, null, $event);
            }
        }
    }

    private function recomputeOpposedWoundedCombatBonus(
        Theah $theah,
        string $location,
        ?EventCardMoved $moveEvent = null,
        ?int $controllerIdOverride = null,
        ?Event $woundOrHealEvent = null
    ): void
    {
        $controllerId = $controllerIdOverride ?? $this->ControllerId;
        if ($controllerId == 0)
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

        $shouldHaveBonus = $this->countWoundedOpposingAtLocation($theah, $location, $controllerId, $moveEvent, $woundOrHealEvent) >= 2;

        if ($shouldHaveBonus && ! $this->OpposedWoundedCombatBonusApplied)
        {
            $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
                $controllerId,
                $this->Id,
                $this->ModifiedCombat,
                $this->ModifiedCombat + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($combatEvent);

            $this->OpposedWoundedCombatBonusApplied = true;
            $this->IsUpdated = true;
        }
        else if (! $shouldHaveBonus && $this->OpposedWoundedCombatBonusApplied)
        {
            $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
                $controllerId,
                $this->Id,
                $this->ModifiedCombat,
                $this->ModifiedCombat - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($combatEvent);

            $this->OpposedWoundedCombatBonusApplied = false;
            $this->IsUpdated = true;
        }
    }

    private function countWoundedOpposingAtLocation(
        Theah $theah,
        string $location,
        int $controllerId,
        ?EventCardMoved $moveEvent = null,
        ?Event $woundOrHealEvent = null
    ): int
    {
        // WHY: LOCATION_PLAYER_HOME is shared across players — getOpposingCharactersAtLocation
        // would count enemies at *their* Homes. You cannot be opposed at Home.
        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            return 0;
        }

        $characters = $theah->getCharactersAtLocation($location);
        $count = 0;

        foreach ($characters as $character)
        {
            // WHY: EventCardMoved fires before DB location update — exclude movers leaving $location.
            if ($moveEvent !== null
                && $character->Id == $moveEvent->cardId
                && $moveEvent->fromLocation == $location
                && $moveEvent->toLocation != $location)
            {
                continue;
            }

            if ($character->Id == $this->Id)
            {
                continue;
            }
            if (! $character->isNotControlledByPlayer($controllerId))
            {
                continue;
            }
            if ($this->characterIsWoundedForCount($character, $woundOrHealEvent))
            {
                $count++;
            }
        }

        // WHY: Same stale-DB — a card moving IN is not listed at $location yet.
        if ($moveEvent !== null
            && $moveEvent->cardId != $this->Id
            && $moveEvent->toLocation == $location
            && $moveEvent->fromLocation != $location)
        {
            $movingCard = $theah->getCardById($moveEvent->cardId);
            if ($movingCard instanceof Character
                && $movingCard->isNotControlledByPlayer($controllerId)
                && $this->characterIsWoundedForCount($movingCard, $woundOrHealEvent))
            {
                $count++;
            }
        }

        return $count;
    }

    /**
     * WHY: EventCharacterWounded/Healed run card handleEvent before/without guaranteed order.
     * If the wounded character has not handled yet, Wounds is stale — apply the event delta.
     */
    private function characterIsWoundedForCount(Character $character, ?Event $woundOrHealEvent): bool
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
