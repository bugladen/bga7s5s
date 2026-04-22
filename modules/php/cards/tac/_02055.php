<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02055;

class _02055 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Dame of Swords');
        $this->Title = clienttranslate('Favored Fortune');
        $this->Image = '02055.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 55;

        $this->initializeFaction('Neutral');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Text = clienttranslate("<p><b>Technique:</b> Copy the effects of a <b>Technique</b> on your participant or one of their attachments. Sink this card.</p>");

        $this->Traits = [
            clienttranslate('Trinket'),
            clienttranslate('Card'),
            clienttranslate('Relic'),
            clienttranslate('Unique'),
        ];

        $this->resetCard();

        $this->Techniques = [
            new Technique_02055(),
        ];
    }
}
