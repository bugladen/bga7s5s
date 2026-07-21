<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03030 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Sworn Swords");
        $this->Image = '03030.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 30;

        $this->initializeFaction("Montaigne");

        $this->Initiative = 36;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Oathsworn"),
            clienttranslate("Challenge")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p>
        <hr>
        <p><b>Diplomat City Action:</b> Engage your performer • Your <b>Duelist</b> at this location issues a [Combat] challenge to target opposing character. Only <b>Duelists</b> may intervene. If the challenge is accepted, add a threat to your participant.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03030(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two different city locations to place Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "03030");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }
}
