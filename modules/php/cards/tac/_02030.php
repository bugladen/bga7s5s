<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _02030 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Fraternité!');
        $this->Image = '02030.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 30;

        $this->initializeFaction('Montaigne');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->WealthCost = 0;
        
        $this->Traits = [
            clienttranslate('Duty'),
            clienttranslate('Ad Hoc'),
            clienttranslate('Camaraderie'),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When an ability is announced • Your character gains <b>Musketeer</b> until the action resolves. <i>(Announcing occurs before performers and targets are chosen.)</i></p><p><b>Reaction:</b> When a challenge is issued • Your character gains <b>Musketeer</b> until the action resolves. <i>(Any subsequent duel is part of the challenge action.)</i></p>");

        $this->resetCard();
    }       
        
}