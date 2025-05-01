<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class AttachmentAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        //If attachment is not attached to a character card, and if character card is not controlled by player, do not show as available
        $card = $this->getOwningCharacter($theah);
        return $card && $card->ControllerId == $playerId;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        //Attachment actions are always performed by the character card the attachment is attached to
        $card = $this->getOwningCharacter($theah);
        return [$card];
    }
    
}   
