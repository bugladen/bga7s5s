<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01045 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Song of Eisen";
        $this->Image = "img/cards/7s5s/045.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 45;

        $this->Faction = "Eisen";
        $this->Initiative = 67;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Prepared",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {
            $event->theah->game->notifyAllPlayers("schemeResolvesMessage", clienttranslate('${scheme_name} now resolves.  
            Reknown will be added to The Forum. 
            ${player_name} will now search the City Deck discard pile for a Mercenary to place on top of the City Deck.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->location = Game::LOCATION_CITY_FORUM;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose a mercenary out of the City Deck discard pile
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01045';
            }
            $event->theah->queueEvent($transition);
        }
    }
}