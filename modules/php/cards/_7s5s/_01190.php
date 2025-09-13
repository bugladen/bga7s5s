<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;

class _01190 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sigurd Ulfsen');
        $this->Image = "img/cards/7s5s/190.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 190;
        
        $this->Title = 'Grizzled Deathseeker';

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 4;
        $this->CityCardNumber = 14;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Pirate',
            'Vesten',
        ];

        $this->resetCard();
    }

    public function canChallenge(): bool
    {
        return false;
    }

    public function addAttachment(Attachment $attachment)
    {
        parent::addAttachment($attachment);

        //Reset combat stat back to original if greater than original
        if ($this->ModifiedCombat > $this->Combat) 
        {
            $this->ModifiedCombat = $this->Combat;
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeIssued && $this->isControlled())
        {
            $defender = $event->theah->getCardById($event->defenderId);
            if ($this->Id != $event->defenderId && $defender->Location == $this->Location && ! $this->Engaged)
            {
                throw new \BgaUserException($event->theah->game->translate("Sigurd Ulfsen must be the target of the challenge if he is En Garge and in the same location."));
            }
        }
    }

}