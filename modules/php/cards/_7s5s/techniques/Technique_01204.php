<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
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

    /**
     * WHY: Dame of Swords (02055) / I Know That Trick (01165) clone this technique
     * onto the participant Character. The original lives on Syrneth Hand and must
     * stay attached; Character owners are valid copies and have no Attachment.
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

        // If activated then this technique will reduce the opponent's Parry by 2 at the start of the next round
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $character = $this->getOwningCharacter($event->theah);
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);

            $this->ReduceAdversaryParry = true;
            $this->markOwnerUpdated($event->theah);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->ReduceAdversaryParry = false;
            $this->markOwnerUpdated($event->theah);
        }

        //Reduce the opponent's Parry by 2 if the technique is activated
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->ReduceAdversaryParry)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $owner = $this->getOwningCard($event->theah);
                $character = $this->getOwningCharacter($event->theah);

                if ($character !== null && $character->Id == $event->adversaryId)
                {
                    $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the Adversary's Parry by %d"), $owner->getInjectCode(), 2);
                    $event->removeParry(2);
                    $this->ReduceAdversaryParry = false;
                    $this->markOwnerUpdated($event->theah);
                }
            }
        }

        // If the event is a new round and the owning character is the actor then reset the ReduceAdversaryParry flag
        if ($event instanceof EventDuelNewRound)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $character = $this->getOwningCharacter($event->theah);
                if ($character !== null && $character->Id == $event->actorId)
                {
                    $this->ReduceAdversaryParry = false;
                    $this->markOwnerUpdated($event->theah);
                }
            }
        }

        // If the duel is over then reset the ReduceAdversaryParry flag
        if ($event instanceof EventDuelEnd)
        {
            if ($this->ownerCanApplyDeferredEffect($event->theah))
            {
                $this->ReduceAdversaryParry = false;
                $this->markOwnerUpdated($event->theah);
            }
        }
    }
}
