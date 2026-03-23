<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01202;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01202 extends CityAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Object of Wonder');
        $this->Image = "01202.jpg";
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

        $this->Text = "<p>Forced: When equipping this card • It always equips to your Leader. (Regardless of who equipped it.)</p><p>Reaction: When your non-Mercenary character is sent to The Locker • Put them into your Approach Deck and send this card to The Locker instead.</p>";

        $this->resetCard();

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