<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01071 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Épée Sanglante";
        $this->Image = "img/cards/7s5s/071.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 71;

        $this->Faction = "Montaigne";
        $this->Initiative = 26;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Challenge", 
            "Duty",
            "Glory",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("schemeResolvesMessage", clienttranslate('${scheme_name} now resolves. ${player_name} must choose a city location to place reknown onto.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = 'pickOneLocationForReknown';
            }
            $event->theah->queueEvent($transition);
        }
    }
}