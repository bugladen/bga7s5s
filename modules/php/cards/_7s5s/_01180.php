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
        $this->Image = "01180.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 180;

        $this->Title = clienttranslate('The Thorn');

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->WealthCost = 4;        
        $this->CityCardNumber = 4;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Academic'),
            clienttranslate("Explorer's Society"),
            clienttranslate('Numa')
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>While equipping an Artifact to one of your characters, it has -1 cost.</p><p><b>City Action:</b> Reveal the top four cards of the City Deck. You may equip a revealed Artifact to your character at this location. Sink the rest.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01180()
        ];
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, Array &$explanations) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        //While equipping an Artifact to any of the controllers characters, Kaj Kousei gives a discount of 1
        if ($performer->ControllerId == $this->ControllerId && $attachment->hasTrait('Artifact'))
        {
            $discount += 1;
            $explanations[] = sprintf($theah->game->translate("%s: -1 for equipping an Artifact to any of the controller's characters"), $this->getInjectCode());
        }
        return $discount;
    }
}