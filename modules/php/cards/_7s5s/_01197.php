<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01197;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01197 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Kalla Forsberg';
        $this->Image = "img/cards/7s5s/197.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 197;
        
        $this->Title = 'Exquisite Smith';

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 4;
        $this->CityCardNumber = 21;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Vesten',
        ];

        $this->Actions = [
            new Action_01197(),
        ];
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment);

        if ($performer->Id == $this->Id)
        {
            $discount += 1;
        }

        return $discount;   
    }
}