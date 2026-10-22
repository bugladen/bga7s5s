<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01035v2 extends Leader implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kaspar Dietrich");
        $this->Image = "01035v2.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 35;

        $this->initializeFaction("Eisen");
        $this->Title = clienttranslate("Old Iron");
        $this->Resolve = 9;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Hero"),
            clienttranslate("General"),
            clienttranslate("Eisen"),
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> When Kaspar parleys • He gains +2 [Influence].</p><p><b>City Action:</b> Engage Kaspar • Reveal cards from the City Deck until you find a Mercenary. Kaspar may recruit it and can parley even while engaged. Sink the rest.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01035v2(),
        ];

        $this->Reactions = [
            new Reaction_01035v2(),
        ];
    }

    public function getParleyDiscount(Theah $theah, Character $performer, bool $parleying, Array &$explanations) : int
    {
        $discount = parent::getParleyDiscount($theah, $performer, $parleying, $explanations);

        // WHY: The City Reaction's +2 is still a parley discount (Influence already
        // reduces the cost). stRecruitComputeDiscount overwrites Game::DISCOUNT after
        // the reaction window, so the accepted reaction arms a flag this method reads.
        // It is not a standing chip change, and it does not apply at Home.
        if ($performer->Id != $this->Id || ! $parleying || ! $theah->cardInCity($this))
        {
            return $discount;
        }

        foreach ($this->getReactions() as $reaction)
        {
            if ($reaction instanceof Reaction_01035v2 && $reaction->ParleyBonusArmed)
            {
                $discount += 2;
                $explanations[] = sprintf($theah->game->translate("%s: -2 from City Reaction when parleying."), $this->getInjectCode());
                break;
            }
        }

        return $discount;
    }

}
