<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentMoved;

class _01047 extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kaspar's Panzerhand");
        $this->Image = "img/cards/7s5s/047.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Eisen';
        
        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->Parry = 4;
        $this->Thrust = 1;

        $this->Traits = [
            'Armor',
            'Eisenfaust',
            'Unique',
        ];

        $this->Reactions = [
            new Reaction_01047(),
        ];

        $this->Techniques = [
            new Technique_01047(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentMoved && $event->attachmentId == $this->Id)
        {
            throw new \BgaUserException(sprintf($event->theah->game->translate('%s cannot be moved.'), $this->Name));
        }
    }

    public function hasEquipRestriction(string $type): bool
    {
        if ($type == 'Armor')
        {
            return false;
        }
        
        return true;
    }
}