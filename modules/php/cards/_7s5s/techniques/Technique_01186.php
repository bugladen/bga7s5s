<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01186 extends Technique
{
    public bool $CancelOpponentManeuvers;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("No Adversary Maneuvers");
        $this->CancelOpponentManeuvers = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        return $inDuel;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        $maryam = $this->getOwningCard($event->theah);

        // If activated then this technique will cancel any Maneuvers that are attempted by the opponent
        // until the start of the next round for the owning character.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->CancelOpponentManeuvers = true;
        }

        // If the event is a new round and Maryam is the actor then reset the CancelOpponentManeuvers flag
        if ($event instanceof EventDuelNewRound && $maryam->Id == $event->actorId)
        {
            $this->CancelOpponentManeuvers = false;            
            $maryam->IsUpdated = true;
        }

        // If the duel is over then reset the CancelOpponentManeuvers flag
        if ($event instanceof EventDuelEnd)
        {
            $this->CancelOpponentManeuvers = false;
            $maryam->IsUpdated = true;
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        $maryam = $this->getOwningCard($event->theah);
        
        if ($event instanceof EventResolveManeuver && $event->adversaryId == $maryam->Id && $this->CancelOpponentManeuvers)
        {
            throw new \BgaUserException($event->theah->game->translate("Technique of Maryam Benu Pleroma is active. Opponent Maneuvers are prevented this round."));
        }
    }
}