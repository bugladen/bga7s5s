<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03042 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('When Least Expected');
        $this->Image = '03042.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 42;

        $this->initializeFaction('Castille');

        $this->Initiative = 66;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Ambush'),
            clienttranslate('Cunning')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to <b>City Docks</b> and <b>City Forums</b>.</p>
<hr />
<p><b>City Action:</b> Engage your performer • They issue a [Finesse] challenge to target opposing character. If your performer is a Duelist, it can only be refused by discarding a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03042(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Docks and The City Forum.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);
        }
    }
}
