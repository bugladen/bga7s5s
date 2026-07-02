<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03029 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Hour of Blood");
        $this->Image = '03029.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 29;

        $this->initializeFaction("Montaigne");

        $this->Initiative = 71;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Porté"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [City Forum] and [City Docks]</p>
        <hr>
        <p><b>Sorcerer City Action:</b> Wound your performer • Choose one: <i>Either</i> move your character at any location to your performer's location, <i>or</i> move your character at your performer's location to any location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03029(),
        ];
    }



    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Forum and The Docks.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);

        }
    }

}
