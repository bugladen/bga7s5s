<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01022 extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Stiletto');
        $this->Image = '01022.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 22;

        $this->initializeFaction('Vodacce');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Knife',
            'Ambrogia',
        ];

        $this->Text = clienttranslate("<p>Reaction: When a challenge is issued at this location, engage this card • Wound the challenger or the character they challenged. (This occurs before intervening.)</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01022(),
        ];
    }
}