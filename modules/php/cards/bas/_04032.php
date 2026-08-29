<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
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

class _04032 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Giacinto");
        $this->Title = clienttranslate("Questionable Confessor");
        $this->Image = "04032.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 32;

        $this->initializeFaction("Castille");

        $this->InPlayXImageOffset = -20;

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Villain"),
            clienttranslate("Academic"),
            clienttranslate("Zealot"),
            clienttranslate("Castille")
        ];

        $this->Text = clienttranslate("<p>Opposing <b>Sorcerers</b> have -1[Influence].</p>
<p><b>En Garde City Action:</b> Target an opposing character • Move Giacinto and that character to the same adjacent <b>City</b> location unless their controller reveals their hand and discards a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04032(),
        ];
    }

    private function isGiacintoInPlay(Theah $theah): bool
    {
        return $this->ControllerId > 0 && ! $theah->game->characterIsInDiscardOrLocker($this);
    }

    private function isOpposingSorcerer(Character $character): bool
    {
        return $character->hasTrait("Sorcerer")
            && $character->ControllerId != 0
            && $this->ControllerId > 0
            && $character->isNotControlledByPlayer($this->ControllerId);
    }

    /**
     * @return list<Character>
     */
    private function getOpposingSorcerersAtLocation(Theah $theah, string $location, ?EventCardMoved $moveEvent = null): array
    {
        if ($this->ControllerId == 0)
        {
            return [];
        }

        // WHY: LOCATION_PLAYER_HOME is shared across players — getCharactersAtLocation(HOME)
        // would count opponents at *their* Homes. "Opposing" at Home is always empty.
        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            return [];
        }

        $characters = $theah->getCharactersAtLocation($location);
        $sorcerers = [];

        foreach ($characters as $character)
        {
            // WHY: EventCardMoved fires before DB updates — exclude movers leaving $location.
            if ($moveEvent !== null
                && $character->Id == $moveEvent->cardId
                && $moveEvent->fromLocation == $location
                && $moveEvent->toLocation != $location)
            {
                continue;
            }

            if ($this->isOpposingSorcerer($character))
            {
                $sorcerers[$character->Id] = $character;
            }
        }

        // WHY: Same stale-DB reason — a Sorcerer moving IN is not listed at $location yet.
        if ($moveEvent !== null
            && $moveEvent->toLocation == $location
            && $moveEvent->fromLocation != $location)
        {
            $movingCard = $theah->getCardById($moveEvent->cardId);
            if ($movingCard instanceof Character && $this->isOpposingSorcerer($movingCard))
            {
                $sorcerers[$movingCard->Id] = $movingCard;
            }
        }

        return array_values($sorcerers);
    }

    private function applyInfluenceReduction(Character $sorcerer, Theah $theah): void
    {
        if ($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION))
        {
            return;
        }

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $sorcerer->Id,
            $sorcerer->ModifiedInfluence,
            $sorcerer->ModifiedInfluence - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($modifiedEvent);

        $sorcerer->addCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION);
        $theah->game->updateCardObjectInDb($sorcerer);

        $theah->game->notify->all("giacintoInfluenceReductionStarted", '', [
            "cardId" => $sorcerer->Id,
        ]);
    }

    private function removeInfluenceReduction(Character $sorcerer, Theah $theah): void
    {
        if (! $sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION))
        {
            return;
        }

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $sorcerer->Id,
            $sorcerer->ModifiedInfluence,
            $sorcerer->ModifiedInfluence + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($modifiedEvent);

        $sorcerer->removeCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION);
        $theah->game->updateCardObjectInDb($sorcerer);

        $theah->game->notify->all("giacintoInfluenceReductionEnded", '', [
            "cardId" => $sorcerer->Id,
        ]);
    }

    private function applyDebuffsAtLocation(Theah $theah, string $location, ?EventCardMoved $moveEvent = null): void
    {
        if (! $this->isGiacintoInPlay($theah) || $location == Game::LOCATION_PLAYER_HOME)
        {
            return;
        }

        foreach ($this->getOpposingSorcerersAtLocation($theah, $location, $moveEvent) as $sorcerer)
        {
            $this->applyInfluenceReduction($sorcerer, $theah);
        }
    }

    private function clearDebuffsAtLocation(Theah $theah, string $location, ?EventCardMoved $moveEvent = null): void
    {
        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            return;
        }

        foreach ($this->getOpposingSorcerersAtLocation($theah, $location, $moveEvent) as $sorcerer)
        {
            $this->removeInfluenceReduction($sorcerer, $theah);
        }
    }

    private function removeFromAllDebuffedSorcerers(Theah $theah): void
    {
        foreach ($theah->getCharactersInPlay() as $character)
        {
            if ($character->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION))
            {
                $this->removeInfluenceReduction($character, $theah);
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->removeFromAllDebuffedSorcerers($event->theah);
            return;
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->removeFromAllDebuffedSorcerers($event->theah);
            return;
        }

        if (! $this->isGiacintoInPlay($event->theah))
        {
            return;
        }

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->clearDebuffsAtLocation($event->theah, $event->fromLocation, $event);
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
                if ($character !== null && $this->isOpposingSorcerer($character))
                {
                    $this->removeInfluenceReduction($character, $event->theah);
                }
            }

            if ($event->toLocation == $this->Location)
            {
                $character = $event->theah->getCharacterById($event->cardId);
                if ($character !== null && $this->isOpposingSorcerer($character))
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

        if ($event instanceof EventCharacterRecruited
            || ($event instanceof EventCharacterMustered && $event->characterId != $this->Id)
            || ($event instanceof EventApproachCharacterPlayed && $event->characterId != $this->Id))
        {
            if ($this->Location == Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null
                && $character->Location == $this->Location
                && $this->isOpposingSorcerer($character))
            {
                $this->applyInfluenceReduction($character, $event->theah);
            }
        }
    }
}
