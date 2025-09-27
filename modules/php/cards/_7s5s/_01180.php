<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01180;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01180 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kaj Kousei");
        $this->Image = "img/cards/7s5s/180.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 180;

        $this->Title = 'The Thorn';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->WealthCost = 4;        
        $this->CityCardNumber = 4;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            "Explorer's Society",
            'Numa'
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01180()
        ];
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment);

        //While equipping an Artifact to any of the controllers characters, Kaj Kousei gives a discount of 1
        if ($performer->ControllerId == $this->ControllerId && $attachment->hasTrait('Artifact'))
        {
            $discount += 1;
        }
        return $discount;
    }
}