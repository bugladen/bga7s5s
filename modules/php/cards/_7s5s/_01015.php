<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01015 extends Scheme implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Great Game";
        $this->Image = "01015.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 15;

        $this->initializeFaction("Vodacce");
        $this->Initiative = 60;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Bureaucracy"), 
            clienttranslate("Zeal"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Docks] and [The Grand Bazaar].</p><hr><p><b>Reaction:</b> After a character is destroyed • Draw a card.</p><p><b>City Action:</b> Destroy your performing character • Wound target character at that location.</p>");

        $this->resetCard();
        
        $this->Actions = [
            new Action_01015(),
        ];

        $this->Reactions = [
            new Reaction_01015(),
        ];
    }


    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  Renown will be added to The Docks and The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);
        }
    }
}