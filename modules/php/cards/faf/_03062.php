<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03062 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Deal with the Devil");
        $this->Image = '03062.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 62;

        $this->initializeFaction('Neutral');

        $this->Initiative = 7;
        $this->PanacheModifier = -2;

        // WHY: Scaffold had Virtue; card art is Villainous – Pact.
        $this->Traits = [
            clienttranslate('Villainous'),
            clienttranslate('Pact'),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [City Forum] and [City Docks]</p>
<hr />
<p><b>Villain City Action:</b> Wound your performer • Muster one of your non-<b>Undead</b>, non-<b>Mercenary</b> characters from <b>The Locker</b> at this location. They gain <b>Monster</b> and <b>Undead</b>. At the end of Dusk, send them to <b>The Locker</b>.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03062(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Forum and The City Docks.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);
        }
    }
}
