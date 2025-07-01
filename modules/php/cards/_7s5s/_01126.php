<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01126 extends Scheme
{
    public string $chosenLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Leshiye of the Wood";
        $this->Image = "img/cards/7s5s/126.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 126;

        $this->Faction = "Usurra";
        $this->Initiative = 34;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Leshiye", 
            "Nature",
        ];
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        $properties['chosenLocation'] = $this->chosenLocation;
        return $properties;
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventReknownAddedToLocation) 
        {
            if ($event->location == $this->chosenLocation)
                throw new \BgaUserException($event->theah->game->translate(("Leshiye of the Wood does not allow Reknown to be placed at its location.")));    
        }

        //We have to allow the reknown to be removed by the scheme itself
        if ($event instanceof EventReknownRemovedFromLocation && $event->source != $this->Name) 
        {
            if ($event->location == $this->chosenLocation)
                throw new \BgaUserException($event->theah->game->translate(("Leshiye of the Wood does not allow Reknown to be removed from its location.")));    
        }

        if ($event instanceof EventLocationClaimed && $event->location == $this->chosenLocation)
        {
            throw new \BgaUserException($event->theah->game->translate(("Leshiye of the Wood does not allow locations to be claimed at its location.")));    
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('<strong>${scheme_name}</strong> now resolves. 
            ${player_name} may first choose an outermost city location. Then they will choose two locations to place reknown onto. '), [
                'i18n' => ['scheme_name'],
                "scheme_name" => $this->Name,
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01126';
            }
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventSchemeMovedToCity && $event->scheme == $this)
        {
            $playerId = $event->theah->game->getActivePlayerId();
            $deck = $event->theah->game->getGameDeckObject();

            $event->theah->game->notifyAllPlayers('01126_2_scheme_moved', 
                clienttranslate('<strong>${card_name}</strong> moves to ${location}'), [
                    'i18n' => ['card_name', 'location'],
                    "cardId" => $this->Id,
                    "card_name" => $this->Name,
                    "location" => $event->location,
            ]);    

            //Get all cards in the chosen location
            $cards = $event->theah->getCardObjectsAtLocation($this->chosenLocation);
            foreach ($cards as $card)
            {
                //Discard all city cards
                if ($card instanceof ICityDeckCard)
                {
                    $discard = $event->theah->createEvent(Events::CardAddedToCityDiscardPile);
                    if ($discard instanceof EventCardAddedToCityDiscardPile)
                    {
                        $discard->cardId = $card->Id;
                        $discard->fromLocation = $this->chosenLocation;
                        $discard->playerId = $playerId;
                    }

                    $event->theah->queueEvent($discard);
                }

                //All characters go home
                else if ($card instanceof Character)
                {
                    $deck->moveCard($card->Id, Game::LOCATION_PLAYER_HOME, $card->ControllerId);;

                    $movedHome = EventFactory::createCardMovedEvent($this->ControllerId, $card->Id, $this->chosenLocation, Game::LOCATION_PLAYER_HOME, false);
                    $event->theah->queueEvent($movedHome);
                }
            }

            //Discard all reknown at chosen location
            $location = $event->theah->getCityLocation($this->chosenLocation);
            if ($location->Reknown > 0)
            {
                $reknown = $event->theah->createEvent(Events::ReknownRemovedFromLocation);
                if ($reknown instanceof EventReknownRemovedFromLocation)
                {
                    $reknown->location = $this->chosenLocation;
                    $reknown->amount = $location->Reknown;
                    $reknown->source = $this->Name;
                }
                $event->theah->queueEvent($reknown);
            }
        }
    }
}