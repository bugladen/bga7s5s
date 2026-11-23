<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Shakedownb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskAttachmentTrait;

// WHY: Class id `05Shakedown_Att` (not `05Shakedown_Blackmail`) — card.card_type is varchar(16).
class _05Shakedown_Att extends FactionAttachment implements IRiskAttachment, IHasActions
{
    use RiskAttachmentTrait;
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Blackmail";
        $this->Image = "05Shakedown.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 28;

        $this->initializeFaction("Castille");

        // WHY: FakeAttachment is what the table sees while the Risk is hidden
        // (createRiskAttachment). Copy printed Text + RPT so full-text tooltips match
        // the Risk, and so a leaked clone in a faction deck does not fatal on
        // stApplyCombatCardStats reading $card->Riposte (01102_Attachment discipline).
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;
        $this->WealthCost = 1;

        $this->InfluenceModifier = -1;
        $this->FakeAttachment = true;

        $this->Traits = [
            "Discovery",
            "Duress",
            "Stealth",
        ];

        $this->Text = "<p>If your performer is a <b>Scoundrel</b> or <b>Villain</b>, this card has -1 cost.</p>
<p><b>Covert Action:</b> Equip this card to target opposing character.</p>
<p>The equipped character has -1 [Influence] and gains: <b>Action:</b> Engage this character • Destroy this card.</p>";

        $this->Actions = [
            new Action_05Shakedownb(),
        ];
    }
}
