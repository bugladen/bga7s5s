<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01101;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01101 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gallegos Blade");
        $this->Image = "01101.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Sword',
            'Aldana',
        ];

        $this->Text = clienttranslate("<p>When the equipped character gambles during a duel, reveal an additional card.</p><p>Technique: -1 Parry • During the adversary's next round, they reveal one less card when they gamble.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_01101(),
        ];
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

        $attachedTo = $this->attachedTo($theah);
        if ($this->isAttached() && $actor->Id == $attachedTo->Id)
        {
            $explanations[] = sprintf($theah->game->translate("%s: +1 for being attached to acting character."), $this->getInjectCode());
            $count += 1;
        }

        return $count;
    }
}