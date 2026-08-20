<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02034;

class _02034 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Torvo Espada');
        $this->Image = '02034.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 34;

        $this->InPlayXImageOffset = -15;

        $this->initializeFaction('Castille');
        $this->Title = clienttranslate('Espadachin Extraordinario');
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate('Hero'),
            clienttranslate('Pirate'),
            clienttranslate('Duelist'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Target an opposing character with 2[Combat] or more • They may issue a [Combat] challenge to Torvo. If they do not, draw a card. If they do, other characters cannot intervene.</p><p><b>Technique:</b> +1[Parry]. If there is an <b>Aldana</b> card in Torvo's dueling line, +1[Riposte] instead.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02034(),
        ];

        $this->Techniques = [
            new Technique_02034(),
        ];
    }
}