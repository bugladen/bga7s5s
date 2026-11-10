<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Seadog;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Seadog extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Mikael Smitski";
        $this->Title = "Man Featuring Pipe";
        $this->Image = "05Seadog.v5.jpg";
        $this->InPlayXImageOffset = 15;
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 24;

        $this->initializeFaction("Ussura");

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Academic",
            "Spy",
            "Seaborn"
        ];

        $this->Text = "<p>While an opponent controls equal or more characters than you, their characters opposing Mikael have -1[influence].</p>
<p><b>Covert Reaction:</b> When a character would move from this location • Cancel the movement.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Seadog(),
        ];
    }

    private function isSeadogInPlay(Theah $theah): bool
    {
        return $this->ControllerId > 0 && ! $theah->game->characterIsInDiscardOrLocker($this);
    }

    /**
     * WHY excludeDestroyedId: EventCharacterDestroyed runs while the victim is still
     * listed in getCharactersInPlay* (runEventHubAfterCards). Without excluding them,
     * a destroy that should flip the equal-or-more gate leaves the aura stuck on.
     */
    private function getControlledCharacterCount(Theah $theah, int $playerId, ?int $excludeDestroyedId = null): int
    {
        $count = 0;
        foreach ($theah->getCharactersInPlayByPlayerId($playerId) as $character)
        {
            if ($excludeDestroyedId !== null && $character->Id == $excludeDestroyedId)
            {
                continue;
            }
            $count++;
        }
        return $count;
    }

    private function opponentOutnumbersOrTies(Theah $theah, int $opponentId, ?int $excludeDestroyedId = null): bool
    {
        if ($this->ControllerId == 0 || $opponentId == 0)
        {
            return false;
        }

        $myCount = $this->getControlledCharacterCount($theah, $this->ControllerId, $excludeDestroyedId);
        $theirCount = $this->getControlledCharacterCount($theah, $opponentId, $excludeDestroyedId);
        return $theirCount >= $myCount;
    }

    private function isOpposingCharacter(Character $character): bool
    {
        return $character->ControllerId != 0
            && $this->ControllerId > 0
            && $character->isNotControlledByPlayer($this->ControllerId);
    }

    private function isEligibleForDebuff(Character $character, Theah $theah, ?int $excludeDestroyedId = null): bool
    {
        return $this->isOpposingCharacter($character)
            && $this->opponentOutnumbersOrTies($theah, $character->ControllerId, $excludeDestroyedId);
    }

    /**
     * @return list<Character>
     */
    private function getEligibleOpposingAtLocation(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $excludeDestroyedId = null): array
    {
        if ($this->ControllerId == 0)
        {
            return [];
        }

        // WHY: LOCATION_PLAYER_HOME is shared across players — opposing at Home is empty.
        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            return [];
        }

        $eligibles = [];
        foreach ($theah->getCharactersAtLocation($location) as $character)
        {
            if ($excludeDestroyedId !== null && $character->Id == $excludeDestroyedId)
            {
                continue;
            }

            // WHY: EventCardMoved fires before DB updates — exclude movers leaving $location.
            if ($moveEvent !== null
                && $character->Id == $moveEvent->cardId
                && $moveEvent->fromLocation == $location
                && $moveEvent->toLocation != $location)
            {
                continue;
            }

            if ($this->isEligibleForDebuff($character, $theah, $excludeDestroyedId))
            {
                $eligibles[$character->Id] = $character;
            }
        }

        // WHY: Same stale-DB reason — a character moving IN is not listed at $location yet.
        if ($moveEvent !== null
            && $moveEvent->toLocation == $location
            && $moveEvent->fromLocation != $location)
        {
            $movingCard = $theah->getCardById($moveEvent->cardId);
            if ($movingCard instanceof Character
                && ($excludeDestroyedId === null || $movingCard->Id != $excludeDestroyedId)
                && $this->isEligibleForDebuff($movingCard, $theah, $excludeDestroyedId))
            {
                $eligibles[$movingCard->Id] = $movingCard;
            }
        }

        return array_values($eligibles);
    }

    private function applyInfluenceReduction(Character $character, Theah $theah): void
    {
        if ($character->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION))
        {
            return;
        }

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $character->Id,
            $character->ModifiedInfluence,
            $character->ModifiedInfluence - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($modifiedEvent);

        $character->addCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("seadogInfluenceReductionStarted", '', [
            "cardId" => $character->Id,
        ]);
    }

    private function removeInfluenceReduction(Character $character, Theah $theah): void
    {
        if (! $character->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION))
        {
            return;
        }

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $character->Id,
            $character->ModifiedInfluence,
            $character->ModifiedInfluence + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($modifiedEvent);

        $character->removeCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("seadogInfluenceReductionEnded", '', [
            "cardId" => $character->Id,
        ]);
    }

    private function applyDebuffsAtLocation(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $excludeDestroyedId = null): void
    {
        if (! $this->isSeadogInPlay($theah) || $location == Game::LOCATION_PLAYER_HOME)
        {
            return;
        }

        foreach ($this->getEligibleOpposingAtLocation($theah, $location, $moveEvent, $excludeDestroyedId) as $character)
        {
            $this->applyInfluenceReduction($character, $theah);
        }
    }

    private function clearDebuffsAtLocation(Theah $theah, string $location): void
    {
        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            return;
        }

        // WHY: Clear by stamped condition, not current eligibility — an outnumbered foe
        // may still hold a stale stamp when Mikael leaves (count resync is separate).
        foreach ($theah->getCharactersAtLocation($location) as $character)
        {
            if ($character->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION))
            {
                $this->removeInfluenceReduction($character, $theah);
            }
        }
    }

    /**
     * WHY full resync: equal-or-more character counts can flip without anyone arriving
     * or leaving Mikael's location (a muster/destroy elsewhere). Giacinto's per-arrival
     * apply is not enough — recompute who should hold the stamp at his current city spot.
     */
    private function resyncDebuffsAtCurrentLocation(Theah $theah, ?int $excludeDestroyedId = null): void
    {
        if (! $this->isSeadogInPlay($theah) || $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $this->removeFromAllDebuffedCharacters($theah);
            return;
        }

        $shouldDebuffIds = [];
        foreach ($this->getEligibleOpposingAtLocation($theah, $this->Location, null, $excludeDestroyedId) as $character)
        {
            $shouldDebuffIds[$character->Id] = $character;
            $this->applyInfluenceReduction($character, $theah);
        }

        foreach ($theah->getCharactersInPlay() as $character)
        {
            if (! $character->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION))
            {
                continue;
            }
            if (isset($shouldDebuffIds[$character->Id]))
            {
                continue;
            }
            $this->removeInfluenceReduction($character, $theah);
        }
    }

    private function removeFromAllDebuffedCharacters(Theah $theah): void
    {
        foreach ($theah->getCharactersInPlay() as $character)
        {
            if ($character->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION))
            {
                $this->removeInfluenceReduction($character, $theah);
            }
        }
    }

    // WHY: Fate's Silence blanks via Theah core-only dispatch — stamped SEADOG
    // Influence reduction would otherwise stick on opposing characters.
    public function onAbilitiesBlanked(Theah $theah): void
    {
        $this->removeFromAllDebuffedCharacters($theah);
    }

    public function onAbilitiesUnblanked(Theah $theah): void
    {
        if ($this->isSeadogInPlay($theah) && $this->Location != Game::LOCATION_PLAYER_HOME)
        {
            $this->applyDebuffsAtLocation($theah, $this->Location);
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->removeFromAllDebuffedCharacters($event->theah);
            return;
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->removeFromAllDebuffedCharacters($event->theah);
            return;
        }

        // WHY: Defense in depth — Theah should skip this handleEvent while blanked, but
        // Card::handleEvent early-return still lets Character subclasses run after parent.
        if ($this->abilitiesAreBlanked())
        {
            return;
        }

        if (! $this->isSeadogInPlay($event->theah))
        {
            return;
        }

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->clearDebuffsAtLocation($event->theah, $event->fromLocation);
            $this->applyDebuffsAtLocation($event->theah, $event->toLocation, $event);
            return;
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id)
        {
            if ($this->Location == Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            if ($event->fromLocation == $this->Location)
            {
                $character = $event->theah->getCharacterById($event->cardId);
                if ($character !== null && $character->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION))
                {
                    $this->removeInfluenceReduction($character, $event->theah);
                }
            }

            if ($event->toLocation == $this->Location)
            {
                $character = $event->theah->getCharacterById($event->cardId);
                if ($character !== null && $this->isEligibleForDebuff($character, $event->theah))
                {
                    $this->applyInfluenceReduction($character, $event->theah);
                }
            }

            return;
        }

        if ($event instanceof EventCharacterMustered && $event->characterId == $this->Id)
        {
            $this->applyDebuffsAtLocation($event->theah, $event->location);
            return;
        }

        if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
        {
            // WHY: Approach lands at Home — no opposing debuff there (shared HOME location).
            return;
        }

        // WHY: Character-count gate can flip on any enter/leave-play, not just local moves.
        if ($event instanceof EventCharacterDestroyed)
        {
            $this->resyncDebuffsAtCurrentLocation($event->theah, $event->characterId);
            return;
        }

        if ($event instanceof EventCharacterRecruited
            || $event instanceof EventCharacterMustered
            || $event instanceof EventApproachCharacterPlayed)
        {
            $this->resyncDebuffsAtCurrentLocation($event->theah);
        }
    }
}
