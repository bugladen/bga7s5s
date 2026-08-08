<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _03cd20 extends CityEventCard implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Early Morning Arrangements');
        $this->Image = '03cd20.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;
        $this->CityCardNumber = 20;

        $this->InPlayXImageOffset = -20;

        $this->Traits = [
            clienttranslate('Prepared'),
            clienttranslate('Timely'),
            clienttranslate('Punctual'),
        ];
        $this->Text = clienttranslate("<p><b>Reaction:</b> At the end of Planning, if this card is in your <b>Home</b> • Move your character to an adjacent <b>City</b> location and discard this card.</p><p><b>City Action:</b> Pressure this location with [Finesse] • If successful, add a city card to this location and put this card in your <b>Home</b> .</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03cd20(),
        ];

        $this->Reactions = [
            new Reaction_03cd20(),
        ];
    }
}
