<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03028 extends Character implements IHasReactions
{
    use ReactionTrait;

    public bool $DuelCombatEqualsInfluenceApplied = false;
    public ?int $CombatBeforeDuelOverride = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Térence Rois");
        $this->Title = clienttranslate('Pompous Perveyor');
        $this->Image = '03028.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 28;

        $this->initializeFaction('Montaigne');

        $this->Resolve = 4;
        $this->Combat = 0;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Diplomat'),
            clienttranslate('Merchant'),
            clienttranslate('Aristocrat'),
            clienttranslate('Montaigne')
        ];

        $this->Text = clienttranslate("<p>Térence cannot issue [Combat] challenges.</p>
<p>While Térence is participating in a duel at [The Grand Bazaar], set his [Combat] as equal to his [Influence].</p>
<p><b>City Reaction:</b> After a character equips an attachment at [The Grand Bazaar] • Draw a card.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03028(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeIssued && $event->challengerId == $this->Id)
        {
            $challengeStat = $event->theah->game->globals->get(Game::CHALLENGE_STAT);

            if ($challengeStat == Game::STAT_COMBAT)
            {
                throw new UserException($event->theah->game->translate('Térence Rois cannot issue Combat challenges.'));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelStarted)
        {
            if ($this->isParticipatingInDuelAtGrandBazaar($event))
            {
                $this->applyCombatEqualsInfluence($event->theah);
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            if ($this->DuelCombatEqualsInfluenceApplied)
            {
                $this->clearCombatEqualsInfluence($event->theah);
            }
        }

        if ($event instanceof EventDefenderSwapped)
        {
            if ($event->newDefenderId == $this->Id && $this->Location == Game::LOCATION_CITY_BAZAAR)
            {
                $this->applyCombatEqualsInfluence($event->theah);
            }
            else if ($event->oldDefenderId == $this->Id)
            {
                $this->clearCombatEqualsInfluence($event->theah);
            }
        }

        if ($event instanceof EventChallengerSwapped)
        {
            if ($event->newChallengerId == $this->Id && $this->Location == Game::LOCATION_CITY_BAZAAR)
            {
                $this->applyCombatEqualsInfluence($event->theah);
            }
            else if ($event->oldChallengerId == $this->Id)
            {
                $this->clearCombatEqualsInfluence($event->theah);
            }
        }

        if ($event instanceof EventCharacterInfluenceModified && $event->CharacterId == $this->Id)
        {
            if ($this->DuelCombatEqualsInfluenceApplied)
            {
                $this->syncCombatToInfluence($event->theah);
            }
        }

        if ($event instanceof EventCharacterCombatModified && $event->CharacterId == $this->Id)
        {
            if ($this->DuelCombatEqualsInfluenceApplied && $event->NewCombat != $this->ModifiedInfluence)
            {
                $this->syncCombatToInfluence($event->theah);
            }
        }
    }

    private function isParticipatingInDuelAtGrandBazaar(EventDuelStarted $event): bool
    {
        if ($event->challengerId != $this->Id && $event->defenderId != $this->Id)
        {
            return false;
        }

        return $this->Location == Game::LOCATION_CITY_BAZAAR;
    }

    private function applyCombatEqualsInfluence(Theah $theah): void
    {
        if ($this->ControllerId == 0) return;

        if ($theah->game->characterIsInDiscardOrLocker($this)) return;

        if ($this->Location != Game::LOCATION_CITY_BAZAAR) return;

        if (! $this->DuelCombatEqualsInfluenceApplied)
        {
            $this->CombatBeforeDuelOverride = $this->ModifiedCombat;
            $this->DuelCombatEqualsInfluenceApplied = true;
            $this->IsUpdated = true;
        }

        $this->syncCombatToInfluence($theah);
    }

    private function clearCombatEqualsInfluence(Theah $theah): void
    {
        if (! $this->DuelCombatEqualsInfluenceApplied) return;

        $restoreCombat = $this->CombatBeforeDuelOverride ?? $this->Combat;

        if ($this->ModifiedCombat != $restoreCombat)
        {
            $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedCombat,
                $restoreCombat,
                $this->getInjectCode()
            );

            $theah->queueEvent($combatEvent);
        }


        $this->DuelCombatEqualsInfluenceApplied = false;
        $this->CombatBeforeDuelOverride = null;
        $this->IsUpdated = true;
    }

    private function syncCombatToInfluence(Theah $theah): void
    {
        if (! $this->DuelCombatEqualsInfluenceApplied) return;

        $targetCombat = $this->ModifiedInfluence;

        if ($this->ModifiedCombat == $targetCombat) return;

        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId,
            $this->Id,
            $this->ModifiedCombat,
            $targetCombat,
            $this->getInjectCode()
        );

        $theah->queueEvent($combatEvent);
    }

}
