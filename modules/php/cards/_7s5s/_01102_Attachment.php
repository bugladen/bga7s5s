<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01102;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskAttachmentTrait;

class _01102_Attachment extends FactionAttachment implements IRiskAttachment, IHasActions
{
    use RiskAttachmentTrait;
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Unfortunate");
        $this->Image = "01102.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 102;

        // WHY: FakeAttachment is what the table sees while the Risk is hidden
        // (createRiskAttachment). Copy printed Text + RPT so full-text tooltips match
        // the Risk, and so a leaked clone in a faction deck (host-death discard bug)
        // does not fatal on stApplyCombatCardStats reading $card->Riposte.
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 3;
        $this->WealthCost = 0;

        $this->FinesseModifier = -1;
        $this->FakeAttachment = true;

        $this->Traits = [
            clienttranslate('Hubris'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> When your round ends • Equip this card to the adversary from your dueling line.</p><p>The equipped character has -1[Finesse] and gains \"<b>Action:</b> Discard two cards • Destroy this card.\"</p>");

        $this->Actions = [
            new Action_01102(),
        ];
    }
}
