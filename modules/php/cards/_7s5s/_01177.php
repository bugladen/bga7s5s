<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01177 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Penya Shows The Way";
        $this->Image = "img/cards/7s5s/177.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 177;

        $this->CityCardNumber = 1;
    }

    public function getGameStateArgs(Game $game): array 
    {
        $args = parent::getGameStateArgs($game);
        $state = $game->gamestate->state_id();

        if ($state == States::DUSK_PHASE_BEGIN_01177)
        {
            //Get the characters at the same location
            $characters = $game->theah->getCharactersAtLocation($this->Location);
            
            //Filter out the characters that are not controlled by the current player
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $game->getActivePlayerId());

            $args['sourceId'] = $this->Id;
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;        
    }

    public function gameActionWithIds(Game $game, array $ids): void
    {
        parent::gameActionWithIds($game, $ids);

        $selectedCharacter = $game->theah->getCharacterById($ids[0]);
        $selectedCharacter->Conditions[] = "Helped By Penya";
        $game->updateCardObjectInDb($selectedCharacter);

        $game->notifyAllPlayers("message", clienttranslate('${player_name} has chosen ${character_name} to follow Penya.'), [
            "player_name" => $game->getActivePlayerName(),
            "character_name" => "<strong>$selectedCharacter->Name</strong>",
        ]);

        $game->gamestate->nextState();
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCardMoved && $event->theah->game->gamestate->state_id() == States::DUSK_PHASE_CLEANUP)
        {
            $card = $event->theah->getCardById($event->cardId);
            if ($card instanceof Character && $card->hasCondition("Helped By Penya"))
            {
                $card->removeCondition("Helped By Penya");
                throw new \BgaUserException("Penya has helped {$card->Name} so they don't go home.");
            }
        }
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskPhaseBegin) 
        {
            $locationName = $this->Location;
            $location = $event->theah->getCityLocation($locationName);

            if ($location->Controller != 0)
            {
                //Get the characters at the same location
                $characters = $event->theah->getCharactersAtLocation($this->Location);
                //Filter out the characters that are not controlled by the current player
                $characters = array_filter($characters, fn($character) => $character->ControllerId == $location->Controller);

                if (count($characters) > 0)
                {
                    $event->theah->game->notifyAllPlayers("message", clienttranslate('${card_name} triggers.  ${player_name} may choose to have one of their characters follow Penya.'), [
                        "card_name" => "<strong>$this->Name</strong>",
                        "player_name" => $event->theah->game->getActivePlayerName(),
                    ]);
                    
                    $transition = $event->theah->createEvent(Events::Transition);
                    if ($transition instanceof EventTransition)
                    {
                        $transition->transition = "01177";
                        $transition->playerId = $location->Controller;
                    }
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }
}