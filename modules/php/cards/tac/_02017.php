<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02017;

class _02017 extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Panzerhand");
        $this->Image = "02017.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 17;

        $this->initializeFaction("Eisen");

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->OffHand = true;

        $this->Traits = [
            clienttranslate("Armor"),
            clienttranslate("Eisenfaust"),
        ];

        $this->Text = clienttranslate("<p>Offhand <i>(Offhand attachments do not count against the limit of one Armor and one Weapon per character. Limit one attachment with Offhand per character.)</i></p><p><b>Reaction:</b> When equipped participant's adversary announces a combat card • It has -1 [Riposte].</p><p><b>Technique:</b> The adversary cannot activate <b>Techniques</b> during their next round.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02017(),
        ];

        $this->Techniques = [
            new Technique_02017(),
        ];
    }
}