<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class AttachmentReaction extends Reaction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function ownerIsAttached(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        if ($owner instanceof Attachment)
        {
            if ( ! $owner->isAttached()) return false;
        }
        
        return true;
    }
}