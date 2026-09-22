<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _04cd29 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Tijani');
        $this->Title = clienttranslate('Port Prowler');

        $this->Image = '04cd29.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->InPlayXImageOffset = -20;

        $this->CityCardNumber = 29;

        $this->WealthCost = 5;

        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Pirate'),
            clienttranslate('Hunter'),
            clienttranslate('Jaragua')
        ];

        $this->Text = clienttranslate("<p>Negotiable <i>(You may Parley while paying for this card.)</i></p>
<p><b>En Garde City Action:</b> Target an engaged character at an adjacent <b>City</b> location • If they have lower [Finesse] than Tijani, wound them. <i>(En Garde abilities require an en garde performer.)</i></p>
<p><b>Gambling Technique:</b> If the adversary has lower [Finesse] than Tijani • Wound them. </p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04cd29(),
        ];

        // WHY: setId scopes ClassId off the shared Technique base name before setOwnerId.
        $technique = new Technique_04cd29();
        $technique->setId("Technique_04cd29");
        $this->Techniques[] = $technique;
    }
}
