<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneRiposte;


class _01074 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Mastercrafted Rapier";
        $this->Image = "img/cards/7s5s/074.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Montaigne';
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            'Weapon',
            'Melee',
            "Sword",
        ];

        $technique = new Technique_PlusOneRiposte();
        $technique->setId("Technique_01074");
        $technique->Name = "Mastercrafted Rapier: +1 Riposte";
        $this->Techniques = [
            $technique,
        ];
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachment->Id == $this->Id) 
        {
            if (!in_array("Duelist", $event->performer->Traits))
                throw new \BgaUserException(_("Mastercrafted Rapier can only be equipped to Duelists."));
        }
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addTechniqueProperties($properties);

        return $properties;
    }
}
