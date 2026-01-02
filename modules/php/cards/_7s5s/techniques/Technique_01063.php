<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01063 extends Technique
{
    private bool $BastienWoundedThisRound = false;
    private bool $IsActive = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Adversary at End of Round");

        $this->IsActive = false;
        $this->BastienWoundedThisRound = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);
        if ($theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $bastien = $this->getOwningCharacter($event->theah);
            $this->IsActive = true;
            $this->BastienWoundedThisRound = false;
            $bastien->IsUpdated = true;
        }

        if ($event instanceof EventCharacterWounded && $this->IsActive)
        {
            $bastien = $this->getOwningCharacter($event->theah);
            if ($event->characterId == $bastien->Id)
            {
                $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);
                if ($inDuel && $event->wounds > 0)
                {
                    $this->BastienWoundedThisRound = true;
                    $bastien->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {
            $bastien = $this->getOwningCharacter($event->theah);
            if ($bastien->Id == $event->actorId && ! $this->BastienWoundedThisRound)
            {
                $game = $event->theah->game;
                $adversaryId = $event->theah->getDuelOpponentId($bastien->Id);
                $woundedEvent = EventFactory::createCharacterBeingWoundedEvent($adversaryId, $bastien->Id, 1, $bastien->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundedEvent);
            }

            $this->IsActive = false;
            $this->BastienWoundedThisRound = false;
            $bastien->IsUpdated = true;
        }
    }    
    
}