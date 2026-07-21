<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseDawnEnding;

class _03001 extends Leader implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Cesca del Rosso");
        $this->Title = clienttranslate("Donna Sinistra");
        $this->Image = "03001.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 1;
        
        $this->initializeFaction("Vodacce");

        $this->Resolve = 7;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 4;

        $this->CrewCap = 6;
        $this->Panache = 2;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Villain"),
            clienttranslate("Sorcerer"),
            clienttranslate("Strega"),
            clienttranslate("Red Hand"),
            clienttranslate("Vodacce"),
        ];

        $this->Text = clienttranslate("<p>At the end of Dawn, draw five cards.</p><p><b>City Reaction:</b> After Cesca performs a <b>Sorcerer</b> ability • Wound an opposing character.</p><p><b>City Action:</b> Target an opposing non-Leader • Move a wound from your Strega at this location to that character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03001(),
        ];

        $this->Reactions = [
            new Reaction_03001(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPhaseDawnEnding && $this->ControllerId > 0)
        {
            $game = $event->theah->game;
            if ($game->characterIsInDiscardOrLocker($this))
            {
                return;
            }

            $game->notify->all("message", clienttranslate('${leader_inject_code}: ${player_name} draws five cards at the end of Dawn.'), [
                "leader_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
            ]);

            for ($i = 0; $i < 5; $i++)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }
        }
    }
}
