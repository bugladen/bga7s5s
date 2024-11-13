<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait DebugTrait
{
    public function dbgIncludeCityCard($className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $this->globals->set(Game::DEBUG_INCLUDE_CITY_CARD, $className);
        }
    }
}