<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _02023 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sir Jack Harding');
        $this->Image = '02023.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 23;

        $this->initializeFaction('Montaigne');
        $this->Title = clienttranslate("Queen Elaine's Vassal");
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Hero'),
            clienttranslate('Knight'),
            clienttranslate('Diplomat'),
            clienttranslate('Avalon')
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Move target opposing non-<b>Leader</b> to an adjacent City location with less Renown.</p><p><b>Technique:</b> If the adversary is a <b>Thug</b> or <b>Mercenary</b> • +1[thrust].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02023(),
        ];

        $this->Techniques = [
            new Technique_02023(),
        ];
    }
}