<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _03003 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Don Constanzo Scarpa");
        $this->Title = clienttranslate("Fearsome Father");
        $this->Image = "03003.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 3;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate("Villain"),
            clienttranslate("Red Hand"),
            clienttranslate("Tyrant"),
            clienttranslate("Vodacce"),
        ];

        $this->Text = clienttranslate("<p><b>City Action: </b>Your <b>Thug</b> at this location issues a <b>Combat</b> challenge to target opposing character.</p><p><b>City Reaction:</b> After your <b>Thug</b> is destroyed • Put a different <b>Thug</b> into play at your <b>Home</b> from your hand or discard pile, at -1 cost.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03003(),
        ];

        $this->Reactions = [
            new Reaction_03003(),
        ];
    }
}
