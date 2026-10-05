<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Coleman;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;

class _05Coleman extends Brute implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Stefano";
        $this->Title = "Scofflaw";
        $this->Image = "05Coleman.v3.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=3 (CAD sequence after Dabney=1 / Cooper=2). Printed CAD-10
        // is the physical set id; class routing uses `_05Coleman`, not CardNumber.
        $this->CardNumber = 3;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 1;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 1;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->WealthCost = 1;

        $this->Traits = [
            "Red Hand",
            "Thug",
            "Recruit",
            "Vodacce",
            "Unique",
            "Brute",
        ];

        $this->Text = "<p><b>Brute</b></p>
<p>Stefano cannot intervene.</p>
<p>Stefano cannot enter play from hand except during a duel.</p>
<p><b>Duelist Maneuver</b>: Put Stefano into play at this location.</p>";

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_05Coleman(),
        ];
    }

    // WHY: Play Brute is High Drama only — never during a duel. Stefano's printed
    // exception is the in-duel Maneuver (and other during-duel hand→play effects),
    // not the Brute menu action.
    public function canBePlayedAsBruteFromHand(): bool
    {
        return false;
    }

    public function canIntervene(): bool
    {
        return false;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // WHY: Penya hard-ban shape — predicate filters UI; eventCheck backstops bypass paths.
        if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id)
        {
            throw new UserException("Stefano cannot intervene.");
        }

        // WHY: At queue time fromLocation is still empty; Location is still the pre-muster
        // zone (HAND / DUELING_LINE / …). Gate hand→play outside duels; Maneuver musters
        // from the dueling line so Location != HAND and passes. During-duel hand musters
        // (e.g. Vittoria) remain legal per printed text.
        if ($event instanceof EventCharacterMustered
            && $event->characterId == $this->Id
            && $this->Location == Game::LOCATION_HAND
            && ! $event->theah->game->globals->get(Game::IN_DUEL, false))
        {
            throw new UserException("Stefano cannot enter play from hand except during a duel.");
        }
    }
}
