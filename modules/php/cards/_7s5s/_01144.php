<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01144 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Filling The Ranks";
        $this->Image = "img/cards/7s5s/144.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 144;

        $this->Faction = "";
        $this->Initiative = 50;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Conscription",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves. ${player_name} must choose a city location to place reknown onto.
            Then if they have the fewest Reknown, they may add a Reknown to a different location.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01144';
            }
            $event->theah->queueEvent($transition);
        }
    }
}