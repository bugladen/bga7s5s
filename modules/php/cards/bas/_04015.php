<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _04015 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Through Thick and Thin");
        $this->Image = "04015.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 15;

        $this->initializeFaction("Eisen");

        $this->Initiative = 4;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate("Camaraderie"),
            clienttranslate("Duty")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p>
<hr />
<p><b>Action:</b> Target an uncontrolled <b>City</b> location • Move your Kaspar Dietrich and your Daniella Dietrich there and they each heal a wound. Then you may discard an available City Card from that location.<i>(You must complete as much of an effect as possible.)</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04015(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two city locations to place Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04015");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }
}
