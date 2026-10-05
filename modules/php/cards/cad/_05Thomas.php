<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Thomas;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;

class _05Thomas extends Brute implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Bruno";
        $this->Title = "Ruffian";
        $this->Image = "05Thomas.v3.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 5;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 1;

        $this->Resolve = 2;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            "Red Hand",
            "Thug",
            "Recruit",
            "Vodacce",
            "Unique",
            "Brute",
        ];

        $this->Text = "<p><b>Brute</b></p>
<p>Bruno cannot intervene.</p>
<p>Bruno cannot enter play from hand except during a duel.</p>
<p><b>Duelist Maneuver</b>: +1[Parry]. Put Bruno into play at this location.</p>";

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_05Thomas(),
        ];
    }

    // WHY: Play Brute is High Drama only — never during a duel. Bruno's printed
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
            throw new UserException("Bruno cannot intervene.");
        }

        // WHY: At check time fromLocation is still empty; Location is still the pre-muster
        // zone (HAND / DUELING_LINE / …). Gate hand→play outside duels; Maneuver musters
        // from the dueling line so Location != HAND and passes. During-duel hand musters
        // (e.g. Vittoria) remain legal per printed text.
        if ($event instanceof EventCharacterMustered
            && $event->characterId == $this->Id
            && $this->Location == Game::LOCATION_HAND
            && ! $event->theah->game->globals->get(Game::IN_DUEL, false))
        {
            throw new UserException("Bruno cannot enter play from hand except during a duel.");
        }
    }
}
