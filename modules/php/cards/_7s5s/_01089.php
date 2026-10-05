<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01089 extends Leader implements IHasReactions
{
    use ReactionTrait;

    public int $AffectedCharacterId = 0;

    // WHY: EventHub clamps ModifiedFinesse with max(0, …). Applying -1 to a 0-FIN
    // character stamps the condition but stores no actual reduction. Later +1 buffs
    // (Elena Sorcery line, Assassin's Garb, etc.) would then show unpenalized FIN.
    // Absorbed=true only when the -1 actually reduced the stored value; while the
    // condition is active and Absorbed=false, re-apply on any later Finesse rise.
    public bool $FinessePenaltyAbsorbed = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Soline el Gato");
        $this->Image = "01089.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 89;

        $this->InPlayXImageOffset = 15;

        $this->initializeFaction("Castille");
        $this->Title = clienttranslate("Prince of Thieves");
        $this->Resolve = 7;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Pirate"),
            clienttranslate("Scoundrel"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p>Your adversaries at Soline's location have -1 [Finesse].</p><p><b>City Reaction:</b> After an Action resolves • Move Soline to an adjacent City location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01089(),
        ];
    }

    private function raiseFinesse(Character $character, Theah $theah)
    {
        if (! $character->hasCondition(Game::SOLINE_EL_GATO_CONDITION))
        {
            return;
        }

        // WHY: Only undo a reduction that was actually stored. If the -1 was floored
        // away at apply time, +1 here would overshoot printed 0.
        if ($this->FinessePenaltyAbsorbed)
        {
            $event = EventFactory::createCharacterFinesseModifedEvent($this->ControllerId, $character->Id, $character->ModifiedFinesse, $character->ModifiedFinesse + 1, $this->getInjectCode());
            $theah->queueEvent($event);
        }

        $this->FinessePenaltyAbsorbed = false;

        $character->removeCondition(Game::SOLINE_EL_GATO_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("solineElGatoConditionEnded", '', [
            "cardId" => $character->Id,
        ]);
    }

    private function lowerFinesse(Character $character, Theah $theah)
    {
        if ($character->hasCondition(Game::SOLINE_EL_GATO_CONDITION))
        {
            return;
        }

        // WHY: Skip queuing -1 when already at 0 — EventHub would clamp NewFinesse=-1
        // to 0 and leave Absorbed falsely implying a stored reduction. Stamp the
        // condition anyway so tooltips show the aura and mid-duel rises can absorb it.
        if ($character->ModifiedFinesse > 0)
        {
            $event = EventFactory::createCharacterFinesseModifedEvent($this->ControllerId, $character->Id, $character->ModifiedFinesse, $character->ModifiedFinesse - 1, $this->getInjectCode());
            $theah->queueEvent($event);
            $this->FinessePenaltyAbsorbed = true;
        }
        else
        {
            $this->FinessePenaltyAbsorbed = false;
        }

        $character->addCondition(Game::SOLINE_EL_GATO_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("solineElGatoConditionStarted", '', [
            "cardId" => $character->Id,
        ]);
    }

    /**
     * When the -1 was floored at apply time, absorb it the first time the victim's
     * Finesse rises above 0 while the condition is still active.
     */
    private function tryAbsorbPendingPenalty(Theah $theah): void
    {
        if ($this->AffectedCharacterId <= 0 || $this->FinessePenaltyAbsorbed)
        {
            return;
        }

        $character = $theah->getCharacterById($this->AffectedCharacterId);
        if ($character === null
            || $theah->game->characterIsInDiscardOrLocker($character)
            || ! $character->hasCondition(Game::SOLINE_EL_GATO_CONDITION)
            || $character->ModifiedFinesse <= 0)
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

        $this->FinessePenaltyAbsorbed = true;
        $this->IsUpdated = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelStarted)
        {
            $challenger = $event->theah->getCharacterById($event->challengerId);
            $defender = $event->theah->getCharacterById($event->defenderId);

            if ($challenger->Location == $this->Location && $challenger->ControllerId == $this->ControllerId)
            {
                $this->lowerFinesse($defender, $event->theah);
                $this->AffectedCharacterId = $defender->Id;
                $this->IsUpdated = true;
            }
            else if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $this->lowerFinesse($challenger, $event->theah);
                $this->AffectedCharacterId = $challenger->Id;
                $this->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            if ($this->AffectedCharacterId > 0)
            {
                $affectedCharacter = $event->theah->getCharacterById($this->AffectedCharacterId);
                if (!$event->theah->game->characterIsInDiscardOrLocker($affectedCharacter))
                {
                    $this->raiseFinesse($affectedCharacter, $event->theah); 
                }

                $this->AffectedCharacterId = 0;
                $this->FinessePenaltyAbsorbed = false;
                $this->IsUpdated = true;
            }
        }

        if ($event instanceof EventDefenderSwapped)
        {
            $challengerId = $event->theah->getDuelOpponentId($event->newDefenderId);
            $challenger = $event->theah->getCharacterById($challengerId);

            if ($challenger->Location == $this->Location && $challenger->ControllerId == $this->ControllerId)
            {
                $oldDefender = $event->theah->getCharacterById($event->oldDefenderId);
                $newDefender = $event->theah->getCharacterById($event->newDefenderId);
                // WHY raise-then-lower: FinessePenaltyAbsorbed is one flag on Soline.
                // Clearing the old victim first consumes Absorbed for them; then lower
                // sets Absorbed for the new victim. Reverse order would +1 the old
                // using the new victim's Absorbed state.
                $this->raiseFinesse($oldDefender, $event->theah);
                $this->lowerFinesse($newDefender, $event->theah);

                $this->AffectedCharacterId = $newDefender->Id;
                $this->IsUpdated = true;
            }
        }

        if ($event instanceof EventChallengerSwapped)
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL, false);
            if (!$inDuel)
            {
                return;
            }

            $defenderId = $event->theah->getDuelOpponentId($event->newChallengerId);
            $defender = $event->theah->getCharacterById($defenderId);

            if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $oldChallenger = $event->theah->getCharacterById($event->oldChallengerId);
                $newChallenger = $event->theah->getCharacterById($event->newChallengerId);
                $this->raiseFinesse($oldChallenger, $event->theah);
                $this->lowerFinesse($newChallenger, $event->theah);

                $this->AffectedCharacterId = $newChallenger->Id;
                $this->IsUpdated = true;
            }
        }

        // WHY: Hub runs before cards on FinesseModifed, so ModifiedFinesse is already
        // the post-change value. Re-absorb a floored -1 the first time FIN rises.
        // Own re-apply event also hits here — Absorbed is already true by then, so no loop.
        if ($event instanceof EventCharacterFinesseModifed
            && $event->CharacterId == $this->AffectedCharacterId)
        {
            $this->tryAbsorbPendingPenalty($event->theah);
        }
    }

}
