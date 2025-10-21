<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01193 extends Technique
{
    public bool $ReduceAdversaryThrust;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-1 Thrust to Adversary");
        $this->ReduceAdversaryThrust = false;
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

        // If activated then this technique will reduce the opponent's Thrust by 1 at the start of the next round
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->ReduceAdversaryThrust = true;
        }

        //Reduce the opponent's Thrust by 1 if the technique is activated
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->ReduceAdversaryThrust)
        {
            $attachment = $this->getOwningCard($event->theah);
            $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

            if ($isAttached)
            {
                $character = $this->getOwningCharacter($event->theah);
                if ($character->Id == $event->adversaryId)
                {
                    $event->thrust = $event->thrust > 0 ? $event->thrust - 1 : 0;
                    $event->explanations[] = $event->theah->game->translate($this->Name);
                    $this->ReduceAdversaryThrust = false;
                    $attachment->IsUpdated = true;
                }
            }
        }

        // If the event is a new round and the owning character is the actor then reset the ReduceOpponentThrust flag
        if ($event instanceof EventDuelNewRound)
        {
            $attachment = $this->getOwningCard($event->theah);
            $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

            if ($isAttached)
            {
                $character = $this->getOwningCharacter($event->theah);
                if ($character->Id == $event->actorId)
                {
                    $this->ReduceAdversaryThrust = false;
                    $attachment->IsUpdated = true;
                }
            }
        }

        // If the duel is over then reset the ReduceOpponentThrust flag
        if ($event instanceof EventDuelEnd)
        {
            $attachment = $this->getOwningCard($event->theah);
            $isAttached = $attachment instanceof Attachment && $attachment->isAttached();

            if ($isAttached)
            {
                $this->ReduceAdversaryThrust = false;
                $attachment->IsUpdated = true;
            }
        }
    }
}