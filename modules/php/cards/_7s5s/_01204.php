<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use BgaUserException;

class _01204 extends CityAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Syrneth Hand');
        $this->Image = "img/cards/7s5s/204.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 204;
        
        $this->CityCardNumber = 28;
        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 1;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Artifact',
            'Syrneth',
            'Unique',
        ];

        $this->resetCard();

        $this->Techniques = [
            new Technique_01204(),
        ];
    }

    public function eventCheck(Event $event)
    {
        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $owner = $this->attachedTo($event->theah);
            $canDiscard = $owner instanceof Character && $owner->IsDying;

            if (! $canDiscard)
            {
                throw new BgaUserException($event->theah->game->translate("Syrneth Hand can't be destroyed or moved from equipped character."));
            }
        }
    }
}