<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class AttachmentAction extends CardAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner == null)
        {
            return false;
        }

        return true;
    }
    
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        //Attachment actions are usually always performed by the character card the attachment is attached to
        $card = $this->getOwningCharacter($theah);
        return [$card];
    }
    
}   
