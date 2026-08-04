<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04011 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Hans Offenheim");
        $this->Title = clienttranslate("Merchant General");
        $this->Image = "04011.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 11;

        $this->initializeFaction("Eisen");

        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->InPlayXImageOffset = -20;

        $this->Traits = [
            clienttranslate("Merchant"),
            clienttranslate("Diplomat"),
            clienttranslate("Noble"),
            clienttranslate("Eisen")
        ];

        $this->Text = clienttranslate("<p><i>En Garde</i> — When an opposing character recruits a <b>Mercenary</b>, they gain +1 cost.</p>
<p><b>City Action: </b>Move target <b>Mercenary</b> at this location <b>Home</b>.
<br><i>(The Mercenary must be in play and not available)</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04011(),
        ];
    }

    public function getParleyDiscount(Theah $theah, Character $performer, bool $parleying, array &$explanations): int
    {
        $discount = parent::getParleyDiscount($theah, $performer, $parleying, $explanations);

        // WHY: Italic En Garde = Engaged=false precondition (Astrid _04cd04). Recruit cost
        // flows only through getParleyDiscount — negative discount = +1 cost (Makepeace shape).
        // Opposing = enemy controller + same location; cardInCity blocks Home-string false positives.
        if (
            ! $this->Engaged
            && $this->isControlled()
            && $performer->isNotControlledByPlayer($this->ControllerId)
            && $performer->Location == $this->Location
            && $theah->cardInCity($performer)
        )
        {
            $discount -= 1;
            $explanations[] = sprintf(
                $theah->game->translate("%s: +1 because performer is opposing Hans Offenheim."),
                $this->getInjectCode()
            );
        }

        return $discount;
    }
}
