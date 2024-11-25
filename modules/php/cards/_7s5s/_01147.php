<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01147 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Let's Haggle";
        $this->Image = "img/cards/7s5s/147.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 147;

        $this->Initiative = 77;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Market",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("schemeResolvesMessage", clienttranslate('${scheme_name} now resolves.  
            Reknown will be added to The Forum and The Grand Bazaar. 
            Cards will be revealed from the City Deck until an Attachment is revealed, then added to The Grand Bazaar.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->location = Game::LOCATION_CITY_FORUM;
                $reknown->amount = 1;
                $reknown->priority = Event::HIGH_PRIORITY;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->location = Game::LOCATION_CITY_BAZAAR;
                $reknown->amount = 1;
                $reknown->priority = Event::HIGH_PRIORITY;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            //Reveal cards from the City Deck until an Attachment is revealed. Sink the rest.
            $game = $event->theah->game;
            $deck = $game->getGameDeckObject();

            $cards = $deck->getCardsInLocation(Game::LOCATION_CITY_DECK);
            $toSink = [];
            $found = false;
            foreach ($cards as $cityCard) {
                $card = $game->getCardObjectFromDb($cityCard['id']);
                if ($card instanceof CityAttachment) {
                    $found = true;
                    $deck->moveCard($cityCard['id'], Game::LOCATION_CITY_BAZAAR);
        
                    $event->theah->game->notifyAllPlayers("schemeResolvesMessage", clienttranslate('${card_name} was found as the top Attachment card in the City Deck.  The cards above it will be sunk.'), [
                        "card_name" => $card->Name,
                    ]);

                    //Create the event
                    $newCard = $event->theah->createEvent(Events::CityCardAddedToLocation);
                    if ($newCard instanceof EventCityCardAddedToLocation) {
                        $newCard->card = $card;
                        $newCard->location = Game::LOCATION_CITY_BAZAAR;
                        $newCard->priority = Event::HIGH_PRIORITY;
                    }
                    $event->theah->queueEvent($newCard);
                }
                else {
                    $toSink[] = $cityCard['id'];
                }

                //Free up memory.
                unset($card);

                if ($found) break;
            }

            //Sink the rest of the cards.
            foreach ($toSink as $cardId) {
                $deck->insertCardOnExtremePosition($cardId, Game::LOCATION_CITY_DECK, false);
            }

            // If not found, inform players.
            if (! $found)
                $event->theah->game->notifyAllPlayers("schemeResolvesMessage", clienttranslate('An Attachment card was not found in City Deck.  No card added to The Grand Bazaar'), []);
        }
    }
}