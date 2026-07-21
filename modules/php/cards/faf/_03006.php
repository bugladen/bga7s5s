<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03006 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Premonition");
        $this->Image = "03006.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 6;

        $this->initializeFaction("Vodacce");

        $this->Initiative = 33;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate("Sorte"),
            clienttranslate("Weave"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p><hr><p><b>Strega Reaction:</b> When your character at your performer's location is targeted by an opponent with more cards in hand than you • They must sink two cards from their hand. <i>(When a card is played, it is removed from hand when it is announced, before choosing targets.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03006(),
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

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "03006");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }
}
