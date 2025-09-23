<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

interface IRiskAttachment
{
    public function setOriginalCardId(int $originalCardId);
    
    public function removeRiskAttachment(Theah $theah);
}