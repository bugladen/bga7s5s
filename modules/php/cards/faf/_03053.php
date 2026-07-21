<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03053 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Curry Favor");
        $this->Image = '03053.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 53;

        $this->initializeFaction('Ussura');

        $this->Initiative = 49;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Trade'),
            clienttranslate('Bureaucracy')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p>
<hr />
<p><b>City Action:</b> Spend a Renown • Claim your performer's location. Each opponent draws a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03053(),
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

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "03053");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }
}
