<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02039 extends Maneuver
{
    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Add Threat');
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $this->IsActive = true;

            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: Adds a threat to both participants at the start of the next round.'), [
                "card_inject_code" => $owner->getInjectCode(),
            ]);

            // WHY: Threat added at end of round applies to the NEXT round, not the current one.
            // The current round's threat resolution is already complete.
            $game = $event->theah->game;
            $pending = $game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0);
            $game->globals->set(Game::PENDING_CHALLENGER_THREAT, $pending + 1);

            $pending = $game->globals->get(Game::PENDING_DEFENDER_THREAT, 0);
            $game->globals->set(Game::PENDING_DEFENDER_THREAT, $pending + 1);
        }
    }
}
