<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01149 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Midnight Shipment";
        $this->Image = "img/cards/7s5s/149.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 149;

        $this->Initiative = 80;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Logistics", 
            "Market",
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('<strong>${scheme_name}</strong> now resolves.  Reknown will be added to The Docks and The Grand Bazaar.  A new City Card will be added to The Docks.'), [
                'i18n' => ['scheme_name'],
                "scheme_name" => $this->Name,
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_DOCKS;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_BAZAAR;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            //Create the event
            $cityCard = $event->theah->game->getCardsOnTopOfCityDeck(1)[0];
            $newCard = EventFactory::createCityCardAddedToLocationEvent($cityCard['id'], Game::LOCATION_CITY_DOCKS);
            $event->theah->queueEvent($newCard);
        }
    }
}