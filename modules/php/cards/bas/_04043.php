<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04043 extends Character implements IHasReactions
{
    use ReactionTrait;

    // WHY: Tracks which adversary currently has the -1 Finesse from Sango's duel passive
    // so swaps / DuelEnd can restore without re-scanning participants. Mirrored into
    // Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID because destroy recreates this card.
    public int $AffectedCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Tomoe Sango');
        $this->Title = clienttranslate('Cleaving Crow');
        $this->Image = '04043.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 43;

        $this->initializeFaction("Ussura");

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Samurai"),
            clienttranslate("Fusō")
        ];

        $this->Text = clienttranslate("<p>During a duel, Sango's adversary has -1[Finesse].</p>
<p><b>En Garde Reaction:</b> At the end of High Drama, if Sango's location is uncontrolled • Claim her location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04043(),
        ];
    }

    /**
     * Safety-net restore for leftover adversary -1 Finesse. Called from stDuelEnd.
     * WHY: Primary clear is immediate on Destroy/Locker (and DuelEnd/swaps). If that
     * path is missed after destroy recreate, this flushes via the pending global.
     */
    public static function clearPendingDebuff(Game $game): void
    {
        $affectedId = (int) $game->globals->get(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID, 0);
        $game->globals->set(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID, 0);

        if ($affectedId <= 0)
        {
            return;
        }

        $affected = $game->theah->getCharacterById($affectedId);
        if ($affected === null)
        {
            $affected = $game->getCardObjectFromDb($affectedId);
        }
        if ($affected === null
            || ! ($affected instanceof Character)
            || $game->characterIsInDiscardOrLocker($affected)
            || ! $affected->hasCondition(Game::TOMOE_SANGO_CONDITION))
        {
            return;
        }

        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $affected->ControllerId,
            $affected->Id,
            $affected->ModifiedFinesse,
            $affected->ModifiedFinesse + 1,
            clienttranslate('Tomoe Sango')
        );
        $game->theah->queueEvent($finesseEvent);

        $affected->removeCondition(Game::TOMOE_SANGO_CONDITION);
        $game->updateCardObjectInDb($affected);

        $game->notify->all("tomoeSangoConditionEnded", '', [
            "cardId" => $affected->Id,
        ]);
    }

    private function lowerAdversaryFinesse(Character $character, Theah $theah): void
    {
        if ($character->hasCondition(Game::TOMOE_SANGO_CONDITION))
        {
            return;
        }

        $event = EventFactory::createCharacterFinesseModifedEvent(
            $this->ControllerId,
            $character->Id,
            $character->ModifiedFinesse,
            $character->ModifiedFinesse - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($event);

        $character->addCondition(Game::TOMOE_SANGO_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("tomoeSangoConditionStarted", '', [
            "cardId" => $character->Id,
        ]);
    }

    private function raiseAdversaryFinesse(Character $character, Theah $theah): void
    {
        if (! $character->hasCondition(Game::TOMOE_SANGO_CONDITION))
        {
            return;
        }

        $event = EventFactory::createCharacterFinesseModifedEvent(
            $this->ControllerId,
            $character->Id,
            $character->ModifiedFinesse,
            $character->ModifiedFinesse + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($event);

        $character->removeCondition(Game::TOMOE_SANGO_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("tomoeSangoConditionEnded", '', [
            "cardId" => $character->Id,
        ]);
    }

    private function clearAdversaryDebuff(Theah $theah): void
    {
        if ($this->AffectedCharacterId <= 0)
        {
            $theah->game->globals->set(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID, 0);
            return;
        }

        $affected = $theah->getCharacterById($this->AffectedCharacterId);
        if ($affected !== null && ! $theah->game->characterIsInDiscardOrLocker($affected))
        {
            $this->raiseAdversaryFinesse($affected, $theah);
        }

        $this->AffectedCharacterId = 0;
        $theah->game->globals->set(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID, 0);
        $this->IsUpdated = true;
    }

    private function applyDebuffToAdversary(int $adversaryId, Theah $theah): void
    {
        $adversary = $theah->getCharacterById($adversaryId);
        if ($adversary === null || $theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return;
        }

        $this->lowerAdversaryFinesse($adversary, $theah);
        $this->AffectedCharacterId = $adversary->Id;
        // WHY: Survive destroy recreate + locker exclusion from buildCity for stDuelEnd flush.
        $theah->game->globals->set(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID, $adversary->Id);
        $this->IsUpdated = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // During a duel, Sango's adversary has -1[Finesse].
        // WHY Soline lifecycle (Started/End/Swaps): Finesse is event-driven; apply only while
        // Sango is a participant. Gate on her Id — not "your character at her location"
        // (Soline's printed aura) and not location match (duels already require same location).
        if ($event instanceof EventDuelStarted)
        {
            if ($event->challengerId == $this->Id)
            {
                $this->applyDebuffToAdversary($event->defenderId, $event->theah);
            }
            else if ($event->defenderId == $this->Id)
            {
                $this->applyDebuffToAdversary($event->challengerId, $event->theah);
            }
        }

        // WHY clear immediately on locker: "During a duel" ends when Sango leaves play.
        // Destroy has runEventHubAfterCards=true — clear here BEFORE EventHub recreates her
        // (wipes AffectedCharacterId). Destroy does not emit CardSentToLocker; that path
        // covers spend-to-locker. Giacinto _04032 same event pair.
        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->clearAdversaryDebuff($event->theah);
            return;
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->clearAdversaryDebuff($event->theah);
            return;
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->clearAdversaryDebuff($event->theah);
        }

        if ($event instanceof EventDefenderSwapped)
        {
            if ($event->oldDefenderId == $this->Id)
            {
                // Sango left the duel as defender — clear her adversary's debuff.
                $this->clearAdversaryDebuff($event->theah);
            }
            else if ($event->newDefenderId == $this->Id)
            {
                // Sango entered as defender — debuff the current challenger.
                $this->clearAdversaryDebuff($event->theah);
                $challengerId = $event->theah->getDuelOpponentId($event->newDefenderId);
                $this->applyDebuffToAdversary($challengerId, $event->theah);
            }
            else if ($this->AffectedCharacterId == $event->oldDefenderId)
            {
                // Sango is challenger; her adversary was swapped — transfer the debuff.
                $this->clearAdversaryDebuff($event->theah);
                $this->applyDebuffToAdversary($event->newDefenderId, $event->theah);
            }
        }

        if ($event instanceof EventChallengerSwapped)
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL, false);
            if (! $inDuel)
            {
                return;
            }

            if ($event->oldChallengerId == $this->Id)
            {
                $this->clearAdversaryDebuff($event->theah);
            }
            else if ($event->newChallengerId == $this->Id)
            {
                $this->clearAdversaryDebuff($event->theah);
                $defenderId = $event->theah->getDuelOpponentId($event->newChallengerId);
                $this->applyDebuffToAdversary($defenderId, $event->theah);
            }
            else if ($this->AffectedCharacterId == $event->oldChallengerId)
            {
                $this->clearAdversaryDebuff($event->theah);
                $this->applyDebuffToAdversary($event->newChallengerId, $event->theah);
            }
        }
    }
}
