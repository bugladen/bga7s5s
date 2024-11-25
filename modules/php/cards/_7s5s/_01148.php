<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01148 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Marooned";
        $this->Image = "img/cards/7s5s/148.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 148;

        $this->Initiative = 22;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Betrayal", 
            "Solitary",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("schemeResolvesMessage", clienttranslate('${scheme_name} now resolves.  Reknown will be added to The Docks and The Grand Bazaar.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->location = Game::LOCATION_CITY_DOCKS;
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
        }
    }
}