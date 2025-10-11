<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01102;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskAttachmentTrait;

class _01102_Attachment extends Attachment implements IRiskAttachment, IHasActions
{
    use RiskAttachmentTrait;
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Unfortunate");
        $this->Image = "img/cards/7s5s/102.jpg";
        $this->FinesseModifier = -1;
        
        $this->Traits = [
            'Hubris',
        ];

        $this->Actions = [
            new Action_01102(),
        ];
    }
}
