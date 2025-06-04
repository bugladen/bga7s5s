<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction_01202;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01202 extends CityAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Object of Wonder';
        $this->Image = "img/cards/7s5s/202.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 202;
        
        $this->CityCardNumber = 26;
        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 1;

        $this->Traits = [
            'Artifact',
            'Syrneth',
            'Unique',
        ];

        $this->Reactions = [
            new Reaction_01202(),
        ];
    }

    public function getRequiredAttachTargetId(Theah $theah, int $originalTargetId): int
    {
        // This only attaches to the leader
        $character = $theah->getCharacterById($originalTargetId);        
        $leader = $theah->getLeaderByPlayerId($character->ControllerId);
        return $leader->Id;
    }
}