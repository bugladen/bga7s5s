<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Nevermore;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Nevermore;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _05Nevermore extends Scheme implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "The Raven's Designs";
        $this->Image = "05Nevermore.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=25 matches CAD sequence; footer CAD-US6 is set designation.
        $this->CardNumber = 25;

        $this->initializeFaction("Ussura");

        $this->Initiative = 13;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Villainous",
            "Hunt",
        ];

        $this->Text = "<p>Add a Renown to [The City Docks].</p>
<hr />
<p><b>Villain Reaction:</b> At the end of Planning •  Target opponent musters Klim Grabiezca from out of play and takes control of him.</p>
<p><b>Leader City Action:</b> Draw a number of cards equal to the number of Artifacts you control.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Nevermore(),
        ];

        $this->Reactions = [
            new Reaction_05Nevermore(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Pattern A — trivial fixed Renown (no player choice / no GameState).
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", '${scheme_inject_code} now resolves. Renown will be added to The City Docks.', [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

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
