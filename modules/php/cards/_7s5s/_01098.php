<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01098 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Cat's Embargo";
        $this->Image = "img/cards/7s5s/098.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 98;

        $this->Faction = "Castille";
        $this->Initiative = 75;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Logistics", 
            "Sabotage",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        //These two locations will each get one Reknown.
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            //Change the active player to the player who played the scheme.
            $event->theah->game->gamestate->changeActivePlayer($event->playerId);

            //Transition to the state where player can choose two locations.
            $event->transition = 'pickTwoLocations';

            $event->theah->game->notifyAllPlayers("schemeResolves", clienttranslate('${scheme_name} now resolves. ${player_name} must choose two city locations to place reknown onto.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$event->scheme->Name}</span>",
                "player_name" => $event->playerName,
            ]);

    }
    }
}