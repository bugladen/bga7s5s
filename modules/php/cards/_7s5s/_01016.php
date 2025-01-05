<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01016 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Plans Within Plans";
        $this->Image = "img/cards/7s5s/016.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 16;

        $this->Faction = "Vodacce";
        $this->Initiative = 73;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Cunning", 
            "Gang",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        //Two locations will each get one Reknown.
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves. ${player_name} must choose two city locations to place reknown onto. 
            Then they must search their deck for a Red Hand Thug, reaveal it, and put it in their hand.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01016';
            }
            $event->theah->queueEvent($transition);
        }
    }
}