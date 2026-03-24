<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01095a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01095b;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;

class _01095 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Patricia Moustakas");
        $this->Image = "01095.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 95;

        $this->initializeFaction("Castille");
        $this->Title = "Domineering Port Captain";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Dockmaster",
            "Numa"
        ];

        $this->Text = clienttranslate("<p>While Patricia is at [The Docks], if she is en garde, she cannot be issued challenges.</p><p>City Action: If Patricia is at [The Docks] • Claim it.</p><p>City Action: If Patricia is at [The Docks], engage her • If you are the first player, draw a card. Otherwise, each opponent discards a card.</p>");

        $this->Actions = [
            new Action_01095a(),
            new Action_01095b(),
        ];

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeIssued && $event->defenderId == $this->Id && $this->Location == Game::LOCATION_CITY_DOCKS && ! $this->Engaged)
        {
            throw new \BgaUserException($event->theah->game->translate("Patricia Moustakas cannot be challenged at The Docks while she is en garde."));
        }
    }
}