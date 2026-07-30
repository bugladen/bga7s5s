<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _04005 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Denounced, Disgraced");
        $this->Image = "04005.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 5;

        $this->initializeFaction("Vodacce");

        // WHY: Card art sun=35, hat=0 — scaffold already matched.
        $this->Initiative = 35;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Villainous"),
            clienttranslate("Purge"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [City Docks].</p>
<hr />
<p><b>Red Hand City Action:</b> Destroy another character you control at your performer's location • Claim this location. Each player discards a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04005(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Docks.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);
        }
    }
}
