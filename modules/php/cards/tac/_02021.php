<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _02021 extends Character implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Grand Merchant Anghos');

        $this->Image = '02021.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 21;

        $this->initializeFaction('Montaigne');
        $this->Title = clienttranslate('Golden Palms');
        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 0;
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate('Dignitary'),
            clienttranslate('Diplomat'),
            clienttranslate('Merchant'),
            clienttranslate('Vesten')
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> When discarding cards to pay costs • One of your attachments discarded this way gains Wealth. <i>(This card counts as two when discarded to pay costs. Send it to The Locker after paying costs.)</i></p><p><b>Technique:</b> If Grand Merchant Anghos has more [Influence] than the adversary • +1[Parry].</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02021(),
        ];

        $this->Techniques = [
            new Technique_02021(),
        ];
    }
}