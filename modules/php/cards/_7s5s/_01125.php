<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01125 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Boar's Guile";
        $this->Image = "img/cards/7s5s/125.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 125;

        $this->Faction = "Usurra";
        $this->Initiative = 40;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Cunning", 
            "Hunt",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves. 
            ${player_name} may first choose a city location to place reknown onto. 
            If they choose not to, they may move a Reknown from a city location to an adjacent location. 
            Lastly, they will choose an enemy character.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01125';
            }
            $event->theah->queueEvent($transition);
        }
    }
}