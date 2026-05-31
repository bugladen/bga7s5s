<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03017 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Noble Sacrifice');
        $this->Image = '03017.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 17;

        $this->initializeFaction('Eisen');

        $this->Initiative = 53;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Heroic'),
            clienttranslate('Finale')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p>
<hr />
<p><b>Reaction:</b> After your character at a <b>City</b> location is destroyed • Wound each opposing character at that location. Each of your characters at that location heals a wound. If the destroyed character was a <b>Zealot</b>, draw a card.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03017(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two different city locations to place Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "03017");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }
}
