<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01177 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Penya Shows The Way");
        $this->Image = "img/cards/7s5s/177.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 177;

        $this->CityCardNumber = 1;
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array 
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        //Get a list of characters that can be shown the way
        if ($state == States::DUSK_PHASE_BEGIN_01177)
        {
            //Get the characters at the same location
            $characters = $game->theah->getCharactersAtLocation($this->Location);
            
            //Filter the characters that are not controlled by the current player
            $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId == $game->getActivePlayerId()));

            $args['sourceId'] = $this->Id;
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        // Get the top 3 cards of the City Deck
        if ($state == States::DUSK_PHASE_BEGIN_01177_2)
        {
            $deckCards = $game->getCardsOnTopOfCityDeck(3);
            $cards = [];
            foreach ($deckCards as $deckCard) {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }
    
            return [
                "cards" => $cards
            ];
        }

        return $args;        
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $actionId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $actionId, $id);

        //Set the character that will be shown the way
        if ($state == States::DUSK_PHASE_BEGIN_01177)
        {
            $selectedCharacter = $game->theah->getCharacterById($id);
            $selectedCharacter->Conditions[] = "Helped By Penya";
            $game->updateCardObjectInDb($selectedCharacter);
    
            $game->notifyAllPlayers("message", clienttranslate('${player_name} has chosen ${character_inject_code} to follow Penya. 
            ${player_name} will now choose the order of the top 3 cards in the City Deck.'), [
                "character_inject_code" => $selectedCharacter->getInjectCode(),
                "player_name" => $game->getActivePlayerName()
            ]);
    
            $game->gamestate->nextState("pickCards");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $actionId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $actionId, $ids);

        //Set the order of the top 3 cards in the City Deck
        if ($state == States::DUSK_PHASE_BEGIN_01177_2)
        {
            $deck = $game->getGameDeckObject();
            $top3Cards = $game->getCardsOnTopOfCityDeck(3);
            $top3Ids = array_map(fn($deckCard) => $deckCard['id'], $top3Cards);

            foreach ($ids as $id) 
            {
                if (!in_array($id, $top3Ids))
                {
                    throw new \BgaUserException(sprintf($game->translate("Card %s is not in the top 3 cards."), $id));
                }

                //Move card to top of City Deck
                $deck->insertCardOnExtremePosition((int)$id, Game::LOCATION_CITY_DECK, true);                
            }

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has chosen the order of the top 3 cards in the City Deck.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            $game->gamestate->nextState();
        }

    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        //Stop characters from going home if they have been helped by Penya
        if ($event instanceof EventCardMoved && $event->theah->game->gamestate->state_id() == States::DUSK_PHASE_CLEANUP)
        {
            $card = $event->theah->getCardById($event->cardId);
            if ($card instanceof Character && $card->hasCondition("Helped By Penya"))
            {
                $card->removeCondition("Helped By Penya");
                throw new \BgaUserException($event->theah->game->translate("Penya has helped {$card->Name} so they don't go home."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        //If Penya is in the city, show the way to a character
        if ($event instanceof EventDuskPhaseBegin && $event->theah->cardInCity($this)) 
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
                    $event->theah->game->notifyAllPlayers("message", clienttranslate('${card_inject_code} triggers.  ${player_name} may choose to have one of their characters follow Penya.'), [
                        "card_inject_code" => $this->getInjectCode(),
                        "player_name" => $event->theah->game->getActivePlayerName(),
                    ]);
                    
                    $transition = $event->theah->createEvent(Events::Transition);
                    if ($transition instanceof EventTransition)
                    {
                        $transition->transition = "01177";
                        $transition->playerId = $location->Controller;
                        $transition->sourceId = $this->Id;
                    }
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }
}