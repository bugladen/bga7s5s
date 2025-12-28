<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01166 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry for each card in your Duel Line");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
            $event->parry += count($cards);
            $event->explanations[] = sprintf($event->theah->game->translate("%s: adds %d Parry for each card in your Duel Line."), $owner->getInjectCode(), count($cards));
        }
    }
}