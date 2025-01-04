<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01015 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Great Game";
        $this->Image = "img/cards/7s5s/015.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 15;

        $this->Faction = "Vodacce";
        $this->Initiative = 60;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bureaucracy", 
            "Zeal",
        ];
    }


    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves.  Reknown will be added to The Docks and The Grand Bazaar.'), [
                "scheme_name" => "<strong>{$this->Name}</strong>",
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
        }
    }
}