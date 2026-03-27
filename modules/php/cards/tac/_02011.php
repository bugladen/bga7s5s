<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;

class _02011 extends Character implements IHasActions, IHasReactions, IHasTechniques, IHasManeuvers
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Katain DeWinter');
        $this->Image = '02011.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 11;

        $this->initializeFaction('Eisen');
        $this->Title = clienttranslate('Eagle Eye');
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate('Knight'),
            clienttranslate('Academic'),
            clienttranslate('Zealot'),
            clienttranslate('Montaigne')
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When Katain performs an ability on a <b>Ranged</b> card, discard a card • Copy the effects.</p><p><b>Technique:</b> Engage Katain's equipped <b>Ranged Weapon</b> • +1[parry].</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02011(),
        ];

        $this->Techniques = [
            new Technique_02011(),
        ];
    }
}