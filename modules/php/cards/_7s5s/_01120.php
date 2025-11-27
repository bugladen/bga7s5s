<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01120;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01120 extends Character implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    private bool $HasAddedInfluence = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pavel Ivanov");
        $this->Image = "img/cards/7s5s/120.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 120;

        $this->Faction = "Ussura";
        $this->Title = "Resolute Scribe";
        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            "Academic",
            "Ussura",
        ];

        $this->HasAddedInfluence = false;

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01120(),
        ];

        $technique = new Technique_PlusOneParry();
        $technique->setId("Technique_01120");
        $this->Techniques = [
            $technique,
        ];
    }

    private function removeInfluence(Theah $theah)
    {
        $theah->game->notify->all("message", clienttranslate('${character_inject_code}: loses 1 Influence because they are no longer at a location controlled by ${player_name}.'), [
            "character_inject_code" => $this->getInjectCode(),
            "player_name" => $theah->game->getPlayerNameById($this->ControllerId),
        ]); 

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId, 
            $this->Id, 
            $this->ModifiedInfluence, 
            $this->ModifiedInfluence - 1,
            $this->getInjectCode()
        );

        $this->HasAddedInfluence = false;
        $this->IsUpdated = true;

        $theah->eventCheck($modifiedEvent);
        $theah->queueEvent($modifiedEvent);
    }

    private function addInfluence(Theah $theah)
    {
        $theah->game->notify->all("message", clienttranslate('${character_inject_code}: gains 1 Influence because they are now at a location controlled by ${player_name}.'), [
            "character_inject_code" => $this->getInjectCode(),
            "player_name" => $theah->game->getPlayerNameById($this->ControllerId),
        ]); 

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId, 
            $this->Id, 
            $this->ModifiedInfluence, 
            $this->ModifiedInfluence + 1,
            $this->getInjectCode()
        );

        $this->HasAddedInfluence = true;
        $this->IsUpdated = true;

        $theah->eventCheck($modifiedEvent);
        $theah->queueEvent($modifiedEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            if ($event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $location = $event->theah->getCityLocation($event->fromLocation);
                if ($location->Controller == $this->ControllerId && $this->HasAddedInfluence)
                {
                    $this->removeInfluence($event->theah);
                }
            }

            if ($event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $location = $event->theah->getCityLocation($event->toLocation);
                if ($location->Controller == $this->ControllerId)
                {
                    $this->addInfluence($event->theah);
                }
            }
        }

        if ($event instanceof EventLocationClaimed && $event->playerId == $this->ControllerId && $event->location == $this->Location)
        {
            $this->addInfluence($event->theah);
        }

        if ($event instanceof EventLocationClaimed && $event->playerId != $this->ControllerId && $event->location == $this->Location && $this->HasAddedInfluence)
        {
            $this->removeInfluence($event->theah);
        }

        if ($event instanceof EventLocationBecomesUncontrolled && $event->location == $this->Location)
        {
            $this->removeInfluence($event->theah);
        }
    }
}