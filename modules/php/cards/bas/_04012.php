<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04012 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Raven");
        $this->Title = clienttranslate("Migratory Bird of Prey");
        $this->Image = "04012.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 12;

        $this->initializeFaction("Eisen");

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Scoundrel"),
            clienttranslate("Hunter"),
            clienttranslate("Ontoquas"),
        ];

        $this->Text = clienttranslate("<p>While Raven has a <b>Ranged</b> card in her dueling line, the adversary cannot perform <b>Maneuvers</b>.</p>
<p><b>City Action:</b> Engage Raven • She issues a [Finesse] challenge to target opposing character. Other characters cannot intervene.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04012(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // WHY EventResolveManeuver + adversaryId (Maryam Technique_01186): blocks only the
        // adversary's maneuvers. Raven's own maneuvers remain legal. Participant gate mirrors
        // Elena _03004 — LOCATION_DUELING_LINE is per-player, not per-character.
        if ($event instanceof EventResolveManeuver && $event->adversaryId == $this->Id)
        {
            if ($this->hasRangedCardInDuelingLine($event->theah))
            {
                throw new UserException(
                    sprintf(
                        $event->theah->game->translate("%s has a Ranged card in her dueling line. The adversary cannot perform Maneuvers."),
                        $this->getInjectCode()
                    )
                );
            }
        }
    }

    private function hasRangedCardInDuelingLine(Theah $theah): bool
    {
        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();
        if ($this->Id != $challengerId && $this->Id != $defenderId)
        {
            return false;
        }

        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $this->ControllerId);
        foreach ($cards as $card)
        {
            if ($card->hasTrait("Ranged"))
            {
                return true;
            }
        }

        return false;
    }
}
