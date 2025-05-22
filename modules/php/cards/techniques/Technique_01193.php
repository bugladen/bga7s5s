<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01193 extends Technique
{
    public bool $ReduceOpponentThrust;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Burnished Cuirass: -1 Thrust to Adversary";
        $this->ShortName = "-1 Adversary Thrust";
        $this->ReduceOpponentThrust = true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        $attachment = $this->getOwningCard($event->theah);
        $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

        // If activated then this technique will reduce the opponent's Thrust by 1 until the start of the next round
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->ReduceOpponentThrust = true;
            $this->setUsed($event->theah, true);
        }

        //Reduce the opponent's Thrust by 1 if the technique is activated
        if ($event instanceof EventDuelCalculateCombatCardStats && $isAttached && $this->ReduceOpponentThrust)
        {
            $character = $this->getOwningCharacter($event->theah);
            if ($character->Id == $event->adversaryId)
            {
                $event->thrust = $event->thrust > 0 ? $event->thrust - 1 : 0;
                $event->explanations[] = $event->theah->game->translate($this->Name);
                $this->ReduceOpponentThrust = false;
                $attachment->IsUpdated = true;
            }
        }

        // If the event is a new round and the owning character is the actor then reset the ReduceOpponentThrust flag
        if ($event instanceof EventDuelNewRound && $isAttached)
        {
            $character = $this->getOwningCharacter($event->theah);
            if ($character->Id == $event->actorId)
            {
                $this->ReduceOpponentThrust = false;
                $attachment->IsUpdated = true;
            }
        }

        // If the duel is over then reset the ReduceOpponentThrust flag
        if ($event instanceof EventDuelEnd && $isAttached)
        {
            $this->ReduceOpponentThrust = false;
            $attachment->IsUpdated = true;
        }
    }
}