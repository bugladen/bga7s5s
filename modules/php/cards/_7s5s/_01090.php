<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01090 extends Character implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Lorenzo de Zepeda");
        $this->Image = "01090.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 90;

        $this->initializeFaction("Castille");
        $this->Title = clienttranslate("Bad News");
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 4;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Scoundrel"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When you announce an ability • Resolve it as if you are not the first player.</p><p><b>Technique:</b> Reveal and replace the top card of the adversary's deck. When their next round begins, they may play it as their combat card by discarding a card. If they do not, wound them.</p>");

        $this->Actions = [
            new Action_01090(),
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01090(),
        ];

        $this->Techniques = [
            new Technique_01090(),
        ];
    }
}

