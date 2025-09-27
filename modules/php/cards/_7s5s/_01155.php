<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01155;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_DestroyPlusOneThrust;

class _01155 extends FactionAttachment implements IHasManeuvers, IHasTechniques, IHasReactions
{
    use ManeuverTrait;
    use TechniqueTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Improvised Weapon");
        $this->Image = "img/cards/7s5s/155.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Weapon',
            'Melee',
            'Ad Hoc',
        ];

        $this->resetCard();

        $technique = new Technique_DestroyPlusOneThrust();
        $technique->setId("Technique_01155");
        $this->Techniques = [
            $technique,
        ];

        $maneuver = new Maneuver_PlusOneParry();
        $maneuver->setId("Maneuver_01155");
        $this->Maneuvers = [
            $maneuver,
        ];

        $this->Reactions = [
            new Reaction_01155(),
        ];
    }
}