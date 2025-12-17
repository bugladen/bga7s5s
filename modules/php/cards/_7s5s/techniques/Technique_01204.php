<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01204 extends Technique
{
    public bool $ReduceAdversaryParry;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound and -2 Parry to Adversary");
        $this->ReduceAdversaryParry = false;
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

        // If activated then this technique will reduce the opponent's Parry by 2 at the start of the next round
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $character = $this->getOwningCharacter($event->theah);
            $woundEvent = EventFactory::createCharacterWoundedEvent($character->Id, $attachment->Id, 1, $attachment->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);

            $this->ReduceAdversaryParry = true;
            $attachment->IsUpdated = true;
        }

        //Reduce the opponent's Parry by 1 if the technique is activated
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->ReduceAdversaryParry)
        {
            $attachment = $this->getOwningCard($event->theah);
            $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

            if ($isAttached)
            {
                $character = $this->getOwningCharacter($event->theah);

                if ($character->Id == $event->adversaryId)
                {
                    $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the Adversary's Parry by %d"), $attachment->getInjectCode(), 2);
                    $event->removeParry(2);
                    $this->ReduceAdversaryParry = false;
                    $attachment->IsUpdated = true;
                }
            }
        }

        // If the event is a new round and the owning character is the actor then reset the ReduceAdversaryParry flag
        if ($event instanceof EventDuelNewRound)
        {
            $attachment = $this->getOwningCard($event->theah);
            $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

            if ($isAttached)
            {
                $character = $this->getOwningCharacter($event->theah);
                if ($character->Id == $event->actorId)
                {
                    $this->ReduceAdversaryParry = false;
                    $attachment->IsUpdated = true;
                }
            }
        }

        // If the duel is over then reset the ReduceAdversaryParry flag
        if ($event instanceof EventDuelEnd)
        {
            $attachment = $this->getOwningCard($event->theah);
            $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

            if ($isAttached)
            {
                $this->ReduceAdversaryParry = false;
                $attachment->IsUpdated = true;
            }
        }
    }
}