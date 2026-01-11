<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01184;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01184 extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claude de la Roche");
        $this->Image = "01184.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 184;

        $this->Title = 'Pompous Sleaze';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->WealthCost = 6;        
        $this->CityCardNumber = 8;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Diplomat',
            'Montaigne',
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01184()
        ];
    }

    public function getInfluencePressureValue(Theah $theah, string $location): int
    {
        $value = parent::getInfluencePressureValue($theah, $location);
        return $value + 1;
    }
}