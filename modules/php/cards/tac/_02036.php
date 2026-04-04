<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02036a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02036b;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02036 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Rumors of the Crimson Roger');
        $this->Image = "02036.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 36;

        $this->initializeFaction('Castille');
        $this->Initiative = 39;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate('Terror'),
            clienttranslate('Rumor'),
        ];

        $this->Text = clienttranslate("<p>Add two Renown to [City Docks].</p><hr><p><b>Pirate Action:</b> Move target opposing non-<b>Pirate</b> <b>Home</b> unless their controller discards a card.</p><p><b>Pirate Action:</b> If your performer is adjacent to [City Docks] • Move them there.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02036a(),
            new Action_02036b(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Two Renown will be added to City Docks.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 2, $this->getInjectCode());
            $event->theah->queueEvent($reknown);
        }
    }
}
