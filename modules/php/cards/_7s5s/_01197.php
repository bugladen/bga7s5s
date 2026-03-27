<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01197;
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

        $this->Name = clienttranslate('Kalla Forsberg');
        $this->Image = "01197.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 197;
        
        $this->Title = clienttranslate('Exquisite Smith');

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 4;
        $this->CityCardNumber = 21;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Vesten'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>When Kalla equips an attachment, it has -1 cost.</p><p>Action: Move an equipped attachment between two of your characters at this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01197(),
        ];
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, Array &$explanations) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        if ($performer->Id == $this->Id)
        {
            $discount += 1;
            $explanations[] = sprintf($theah->game->translate("%s: -1 because performer is Kalla Forsberg."), $this->getInjectCode());
        }

        return $discount;   
    }
}