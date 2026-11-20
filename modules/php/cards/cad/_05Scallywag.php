<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Scallywag;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _05Scallywag extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Friends at Sea";
        $this->Image = "05Scallywag.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=24 matches CAD sequence; footer BAS-C is set designation.
        $this->CardNumber = 24;

        $this->initializeFaction("Castille");

        $this->Initiative = 21;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Daring",
            "Camaraderie",
        ];

        // WHY: Text was Forum or Docks (pick); Eddie updated to and — fixed dual like Wildcall.
        $this->Text = "<p>Add a Renown to [The City Forum] and [The City Docks]</p>
<hr />
<p><b>Reaction:</b> When an opponent's <b>Leader</b> is wounded as a result of your performer's challenge or during their duel • Muster a <b>Pirate</b> engaged at their location.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Scallywag(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", '${scheme_inject_code} now resolves. Renown will be added to The City Forum and The City Docks.', [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $forum = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                Game::LOCATION_CITY_FORUM,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($forum);

            $docks = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                Game::LOCATION_CITY_DOCKS,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($docks);
        }
    }
}
