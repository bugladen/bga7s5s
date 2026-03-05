<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01037;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01037 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Edeline Trinken");
        $this->Image = "01037.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 37;

        $this->initializeFaction("Eisen");
        $this->Title = "Mistress of the House";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 0;

        $this->Traits = [
            "Innkeeper",
            "Eisen",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01037(),
        ];
    }

    private function updateInfluence(Theah $theah, string $location, int $adjustment = 0)
    {
        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            $characters = $theah->getCharactersAtHomeByPlayerId($this->ControllerId);
        }
        else
        {
            $characters = $theah->getCharactersAtLocation($location);
        }
        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId, 
            $this->Id, 
            $this->ModifiedInfluence, 
            count($characters) + $adjustment,
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->updateInfluence($event->theah, $event->toLocation, 1);
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
        {
            if ($event->toLocation != Game::LOCATION_PLAYER_HOME || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateInfluence($event->theah, $event->toLocation, 1);
            }
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
        {
            if ($event->fromLocation != Game::LOCATION_PLAYER_HOME || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateInfluence($event->theah, $event->fromLocation, -1);
            }
        }

        if ($event instanceof EventCharacterMustered && ($event->characterId == $this->Id || $event->location == $this->Location))
        {
            $this->updateInfluence($event->theah, $event->location, 1);
        }

        if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
        {
            $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location)
            {
                $this->updateInfluence($event->theah, $character->Location, -1);
            }
        }

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location)
            {
                $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME, 1);
            }
        }
    }
}