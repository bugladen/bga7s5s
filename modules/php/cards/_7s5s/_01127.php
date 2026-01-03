<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01127;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusTwoThrust;

class _01127 extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Grandfather's Hammer");
        $this->Image = "img/cards/7s5s/127.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 5;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Hammer',
            'Unique',
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01127(),
        ];

        $technique = new Technique_PlusTwoThrust();
        $technique->setId("Technique_01127");
        $this->Techniques[] = $technique;
    }
}