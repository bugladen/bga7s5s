<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03026;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03026 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Angeline Dèmone');
        $this->Title = clienttranslate('Uneasy Ally');
        $this->Image = '03026.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 26;

        $this->initializeFaction('Montaigne');

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate('Villain'),
            clienttranslate('Duelist'),
            clienttranslate('Pirate'),
            clienttranslate('Sorcerer'),
            clienttranslate('Montaigne')
        ];

        $this->Text = clienttranslate("<p>While you control another <b>Sorcerer</b> at Angeline's location, she has +1 [Influence].</p>
        <p><b>Action</b>: Discard a card • Move Angeline to an adjacent <b>City</b> location. Then, If the discarded card was a <b>Sorcery</b>, wound an opposing character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03026(),
        ];
    }

    private function updateInfluence(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $controllerIdOverride = null): void
    {
        $controllerId = $controllerIdOverride ?? $this->ControllerId;

        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            $characters = $theah->getCharactersAtHomeByPlayerId($controllerId);
        }
        else
        {
            $characters = $theah->getCharactersAtLocation($location);
        }

        $bonus = 0;
        foreach ($characters as $character)
        {
            // WHY: EventCardMoved fires before the DB location is updated, so a card moving
            // out is still listed at $location. Exclude it from the count.
            if ($moveEvent !== null
                && $character->Id == $moveEvent->cardId
                && $moveEvent->fromLocation == $location
                && $moveEvent->toLocation != $location)
            {
                continue;
            }

            if ($character->Id != $this->Id
                && $character->ControllerId == $controllerId
                && $character->hasTrait("Sorcerer"))
            {
                $bonus = 1;
                break;
            }
        }

        // WHY: Same stale-DB reason for a card moving in — it isn't listed at $location yet.
        if ($bonus == 0
            && $moveEvent !== null
            && $moveEvent->cardId != $this->Id
            && $moveEvent->toLocation == $location
            && $moveEvent->fromLocation != $location)
        {
            $movingCard = $theah->getCardById($moveEvent->cardId);
            if ($movingCard !== null
                && $movingCard->ControllerId == $controllerId
                && $movingCard->hasTrait("Sorcerer"))
            {
                $bonus = 1;
            }
        }

        $newInfluence = $this->Influence + $bonus;
        if ($newInfluence == $this->ModifiedInfluence)
        {
            return;
        }

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $controllerId,
            $this->Id,
            $this->ModifiedInfluence,
            $newInfluence,
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->updateInfluence($event->theah, $event->toLocation, $event);
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
        {
            if ($event->toLocation != Game::LOCATION_PLAYER_HOME || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateInfluence($event->theah, $this->Location, $event);
            }
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
        {
            if ($event->fromLocation != Game::LOCATION_PLAYER_HOME || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateInfluence($event->theah, $this->Location, $event);
            }
        }

        if ($event instanceof EventCharacterMustered && ($event->characterId == $this->Id || $event->location == $this->Location))
        {
            $this->updateInfluence($event->theah, $event->location);
        }

        if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
        {
            // WHY: ControllerId may not yet be propagated to the in-memory Character when
            // her own approach event fires — use $event->playerId so the home lookup works.
            $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME, null, $event->playerId);
        }

        if ($event instanceof EventApproachCharacterPlayed
            && $event->characterId != $this->Id
            && $this->Location == Game::LOCATION_PLAYER_HOME
            && $event->playerId == $this->ControllerId)
        {
            $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location)
            {
                $this->updateInfluence($event->theah, $this->Location);
            }
        }

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location)
            {
                $this->updateInfluence($event->theah, $this->Location);
            }
        }
    }
}
