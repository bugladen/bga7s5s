<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01098 extends Scheme
{
    public int $EmbargoedCardId = 0;

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

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves. ${player_name} must choose two city locations to place reknown onto.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01098';
            }
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhasePlanningEnd) 
        {
            $playerName = $event->theah->game->getPlayerNameById($this->ControllerId);

            //Pick an opponent. That opponent will reveal a random card from their hand.
            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} triggers a Forced Reaction for the End of Planning Phase.  ${player_name} must choose an opponent (via their leader) to reveal a random card from their hand.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $playerName,
            ]);

            //Transition to the state where player chooses an opponent.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $this->ControllerId;
                $transition->transition = '01098';
            }
            $event->theah->queueEvent($transition);
        }
    }
}