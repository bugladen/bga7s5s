<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01072 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Réputation Méritée";
        $this->Image = "img/cards/7s5s/072.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 72;

        $this->Faction = "Montaigne";
        $this->Initiative = 62;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Camaraderie", 
            "Honor",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("schemeResolves", clienttranslate('${scheme_name} now resolves. ${player_name} must choose a city location with no Reknown to place reknown onto.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = 'pickOneLocationForReknownWithNone';
            }
            $event->theah->queueEvent($transition);
        }
    }
}