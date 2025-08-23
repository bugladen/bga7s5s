<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01148;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01148 extends Scheme implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Marooned");
        $this->Image = "img/cards/7s5s/148.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 148;

        $this->Initiative = 22;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Betrayal", 
            "Solitary",
        ];

        $this->Actions = [
            new Action_01148(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves.  Reknown will be added to The Docks and The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);
        }
    }
}