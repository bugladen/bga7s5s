<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04022 extends Character implements IHasReactions
{
    use ReactionTrait;

    // WHY: Flag ±1 (Ise/Benci), not Angeline absolute base+bonus — attachments also
    // mutate ModifiedFinesse / ModifiedInfluence and absolute would clobber them.
    public bool $DuelistAuraApplied = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Axelle Hubert");
        $this->Title = clienttranslate("By Hook or By Crook");
        $this->Image = "04022.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 22;

        $this->initializeFaction("Montaigne");

        $this->InPlayXImageOffset = 10;

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Scoundrel"),
            clienttranslate("Diplomat"),
            clienttranslate("Montaigne")
        ];

        $this->Text = clienttranslate("<p>While you control a <b>Duelist</b> at this location, Axelle gains +1[Finesse] and +1 [Influence].</p>
<p><b>Reaction:</b> During a duel, after an opposing adversary announces their combat card • Your participant gains a threat. If Axelle is en garde, the adversary also gains a threat.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04022(),
        ];
    }

    private function hasControlledDuelistAtLocation(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $controllerIdOverride = null): bool
    {
        $controllerId = $controllerIdOverride ?? $this->ControllerId;
        if ($controllerId == 0)
        {
            return false;
        }

        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            $characters = $theah->getCharactersAtHomeByPlayerId($controllerId);
        }
        else
        {
            $characters = $theah->getCharactersAtLocation($location);
        }

        foreach ($characters as $character)
        {
            // WHY: EventCardMoved fires before DB updates — a card moving OUT is still listed.
            if ($moveEvent !== null
                && $character->Id == $moveEvent->cardId
                && $moveEvent->fromLocation == $location
                && $moveEvent->toLocation != $location)
            {
                continue;
            }

            if ($character->ControllerId == $controllerId && $character->hasTrait("Duelist"))
            {
                return true;
            }
        }

        // WHY: Same stale-DB reason — a Duelist moving IN is not listed yet.
        if ($moveEvent !== null
            && $moveEvent->toLocation == $location
            && $moveEvent->fromLocation != $location)
        {
            $movingCard = $theah->getCardById($moveEvent->cardId);
            if ($movingCard !== null
                && $movingCard->ControllerId == $controllerId
                && $movingCard->hasTrait("Duelist"))
            {
                return true;
            }
        }

        return false;
    }

    private function updateDuelistAura(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $controllerIdOverride = null): void
    {
        if ($theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }

        $controllerId = $controllerIdOverride ?? $this->ControllerId;
        if ($controllerId == 0)
        {
            return;
        }

        $shouldHave = $this->hasControlledDuelistAtLocation($theah, $location, $moveEvent, $controllerId);

        if ($shouldHave && ! $this->DuelistAuraApplied)
        {
            $this->DuelistAuraApplied = true;
            $this->IsUpdated = true;

            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $controllerId,
                $this->Id,
                $this->ModifiedFinesse,
                $this->ModifiedFinesse + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($finesseEvent);

            $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                $controllerId,
                $this->Id,
                $this->ModifiedInfluence,
                $this->ModifiedInfluence + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($influenceEvent);
        }
        else if (! $shouldHave && $this->DuelistAuraApplied)
        {
            $this->DuelistAuraApplied = false;
            $this->IsUpdated = true;

            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $controllerId,
                $this->Id,
                $this->ModifiedFinesse,
                $this->ModifiedFinesse - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($finesseEvent);

            $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                $controllerId,
                $this->Id,
                $this->ModifiedInfluence,
                $this->ModifiedInfluence - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($influenceEvent);
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->updateDuelistAura($event->theah, $event->toLocation, $event);
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
        {
            if ($event->toLocation != Game::LOCATION_PLAYER_HOME || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateDuelistAura($event->theah, $this->Location, $event);
            }
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
        {
            if ($event->fromLocation != Game::LOCATION_PLAYER_HOME || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateDuelistAura($event->theah, $this->Location, $event);
            }
        }

        if ($event instanceof EventCharacterMustered && ($event->characterId == $this->Id || $event->location == $this->Location))
        {
            $this->updateDuelistAura($event->theah, $event->location);
        }

        if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
        {
            // WHY: ControllerId may not yet be on the in-memory card — use event playerId.
            $this->updateDuelistAura($event->theah, Game::LOCATION_PLAYER_HOME, null, $event->playerId);
        }

        if ($event instanceof EventApproachCharacterPlayed
            && $event->characterId != $this->Id
            && $this->Location == Game::LOCATION_PLAYER_HOME
            && $event->playerId == $this->ControllerId)
        {
            $this->updateDuelistAura($event->theah, Game::LOCATION_PLAYER_HOME);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null && $character->Location == $this->Location)
            {
                $this->updateDuelistAura($event->theah, $this->Location);
            }
        }

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null && $character->Location == $this->Location)
            {
                $this->updateDuelistAura($event->theah, $this->Location);
            }
        }
    }
}
