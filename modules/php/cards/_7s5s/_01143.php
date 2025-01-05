<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01143 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Contempt and Hatred";
        $this->Image = "img/cards/7s5s/143.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 143;

        $this->Faction = "";
        $this->Initiative = 43;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Demoralize", 
            "Duress",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves.  Reknown will be added to The City Forum.
            Then ${player_name} may choose a city location to place reknown onto. If they do, all City Cards will be discarded from that location.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_FORUM;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose any location.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01143';
            }
            $event->theah->queueEvent($transition);
        }
    }    
}