<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

class _01099 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Shifting Blame";
        $this->Image = "img/cards/7s5s/099.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 99;

        $this->Faction = "Castille";
        $this->Initiative = 10;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Cunning", 
            "Rumor",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        //When this scheme resolves, add 1 reknown to the City Docks
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->location = Game::LOCATION_CITY_DOCKS;
                $reknown->amount = 1;
                $reknown->priority = Event::HIGH_PRIORITY;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);
        }
    }
}