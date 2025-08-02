<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class AttachmentAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        //Attachment actions are always performed by the character card the attachment is attached to
        $card = $this->getOwningCharacter($theah);
        return [$card];
    }
    
}   
