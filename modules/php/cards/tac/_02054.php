<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02054;

class _02054 extends FactionAttachment implements IHasManeuvers, IHasTechniques
{
    use ManeuverTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Concealed Flintlock');
        $this->Image = '02054.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 54;

        $this->initializeFaction('Neutral');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->OffHand = true;

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Weapon'),
            clienttranslate('Ranged'),
            clienttranslate('Pistol'),
            clienttranslate('Stealth')
        ];

        $this->Text = clienttranslate("<p>Offhand <i>(Offhand attachments do not count against the limit of one Armor and one Weapon per character. Limit one attachment with Offhand per character.)</i></p><p><b>Maneuver:</b> Equip this card to your participant from your dueling line. If they have 1[Combat] or less, draw a card.</p><p><b>Technique:</b> Your adversary may suffer a wound. If they do not, +1[Parry].</p>");

        $this->resetCard();

        $maneuver = new Maneuver_02054();
        $maneuver->setId("Maneuver_02054");
        $this->Maneuvers = [
            $maneuver,
        ];

        $technique = new Technique_02054();
        $technique->setId("Technique_02054");
        $this->Techniques = [
            $technique,
        ];
    }
}
