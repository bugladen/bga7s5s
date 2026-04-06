<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02043a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02043b;

class _02043 extends Character implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate('Miyato and Ota');
        $this->Image = '02043.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 43;

        $this->initializeFaction('Ussura');
        $this->Title = clienttranslate('Two of a Kind');
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            clienttranslate('Twins'),
            clienttranslate('Duelist'),
            clienttranslate('Fusō'),
        ];

        $this->Text = clienttranslate("<p><b>Technique:</b> After Miyato and Ota perform a <b>Maneuver</b> • Copy the effects. Send that combat card to <b>The Locker</b>. Usable once per day.</p><p><b>Technique:</b> Choose a <b>Flourish</b> in your discard pile and place it on top of your deck.</p>");
        $this->resetCard();

        $this->Techniques = [
            new Technique_02043a(),
            new Technique_02043b(),
        ];
    }
}