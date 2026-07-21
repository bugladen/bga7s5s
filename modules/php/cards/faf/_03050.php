<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03050;

class _03050 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Mōri Daichi");
        $this->Title = clienttranslate("Solemn Swordsman");
        $this->Image = '03050.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 50;

        $this->InPlayXImageOffset = -20;

        $this->initializeFaction("Ussura");

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Kannushi"),
            clienttranslate("Fusō")
        ];

        $this->Text = clienttranslate("<p>Daichi cannot refuse challenges issued by characters with greater [Combat].</p>
<p>When Daichi issues a challenge, characters with greater [Combat] cannot refuse.</p> 
<p><b>Technique</b>: If Daichi's combat card is a <b>Flourish</b> or <b>Sorcery</b> • +1[Riposte].</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_03050(),
        ];
    }

    /**
     * WHY character-identity gate (not a new CHALLENGE_TYPE): both passives apply to ANY
     * challenge involving Daichi — including NORMAL challenges issued *to* him by opponents.
     * "greater" is strict >; equals may still refuse.
     */
    public static function challengeRefusalBlocked(Character $challenger, Character $defender): bool
    {
        if ($challenger instanceof self)
        {
            return $defender->ModifiedCombat > $challenger->ModifiedCombat;
        }

        if ($defender instanceof self)
        {
            return $challenger->ModifiedCombat > $defender->ModifiedCombat;
        }

        return false;
    }
}
