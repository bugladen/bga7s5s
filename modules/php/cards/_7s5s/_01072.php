<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01072 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Réputation Méritée");
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

        $this->Actions = [
            new Action_01072(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a city location with no Reknown to place reknown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01072';
            }
            $event->theah->queueEvent($transition);
        }
    }
}