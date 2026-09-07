<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
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

    /**
     * WHY: Dame of Swords (02055) / I Know That Trick (01165) clone this technique
     * onto the participant Character. The original lives on Burnished Cuirass and
     * must stay attached; Character owners are valid copies and have no Attachment.
     */
    private function ownerCanApplyDeferredEffect(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        if ($owner instanceof Attachment)
        {
            return $owner->isAttached();
        }

        return $owner instanceof Character;
    }

    private function markOwnerUpdated(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // If activated then this technique will reduce the opponent's Thrust by 1 at the start of the next round
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->ReduceAdversaryThrust = true;
            $this->markOwnerUpdated($event->theah);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->ReduceAdversaryThrust = false;
            $this->markOwnerUpdated($event->theah);
        }

        //Reduce the opponent's Thrust by 1 if the technique is activated
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->ReduceAdversaryThrust)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $owner = $this->getOwningCard($event->theah);
                $character = $this->getOwningCharacter($event->theah);
                if ($character !== null && $character->Id == $event->adversaryId)
                {
                    $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the Adversary's Thrust by %d"), $owner->getInjectCode(), 1);
                    $event->removeThrust(1);
                    $this->ReduceAdversaryThrust = false;
                    $this->markOwnerUpdated($event->theah);
                }
            }
        }

        // If the event is a new round and the owning character is the actor then reset the ReduceOpponentThrust flag
        if ($event instanceof EventDuelNewRound)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $character = $this->getOwningCharacter($event->theah);
                if ($character !== null && $character->Id == $event->actorId)
                {
                    $this->ReduceAdversaryThrust = false;
                    $this->markOwnerUpdated($event->theah);
                }
            }
        }

        // If the duel is over then reset the ReduceOpponentThrust flag
        if ($event instanceof EventDuelEnd)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $this->ReduceAdversaryThrust = false;
                $this->markOwnerUpdated($event->theah);
            }
        }
    }
}
