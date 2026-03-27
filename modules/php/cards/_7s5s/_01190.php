<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01190 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sigurd Ulfsen');
        $this->Image = "01190.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 190;
        
        $this->Title = clienttranslate('Grizzled Deathseeker');

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 4;
        $this->CityCardNumber = 14;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Pirate'),
            clienttranslate('Vesten'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>Sigurd's [Combat] cannot be increased and he cannot issue challenges.</p><p>While Sigurd is en garde, he must be the target of enemy challenges at this location.</p>");

        $this->resetCard();
    }

    public function canChallenge(): bool
    {
        return false;
    }

    public function addAttachment(Theah $theah, Attachment $attachment)
    {
        parent::addAttachment($theah, $attachment);

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
            $challenger = $event->theah->getCardById($event->challengerId);
            if ($challenger->ControllerId != $this->ControllerId && //Challenger is not the same player as Sigurd
                $this->Id != $event->defenderId && //Defender is not Sigurd
                $defender->Location == $this->Location && //Defender is in the same location as Sigurd
                ! $this->Engaged) //Sigurd is not engaged
            {
                throw new \BgaUserException($event->theah->game->translate("Sigurd Ulfsen must be the target of the challenge if he is en garde and in the same location."));
            }
        }
    }

}