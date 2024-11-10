<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;

trait EventHandler
{
    public function handleEvent($event)
    {
        switch (true) {
            case $event instanceof EventCityCardAddedToLocation:
                $this->cards[] = $event->card;
                break;
            case $event instanceof EventApproachCharacterPlayed:
                // Remove this card from the internal approach cards
                foreach ($this->approachCards as $key => $card) {
                    if ($card->Id == $event->character->Id) {
                        unset($this->approachCards[$key]);
                        break;
                    }
                }
                // Add to the city cards
                $this->cards[] = $event->character;                
                break;
        }
    }
}