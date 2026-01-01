<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01181 extends AttachmentReaction
{
    public Array $HealTargetIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Heal Wounds');

        $this->HealTargetIds = [];
    }

    public function getReactionDescription(Theah $theah): string
    {
        //Pop the first target id from the array
        $id = $this->HealTargetIds[0];
        $character = $theah->getCharacterById($id);
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may choose to Heal Wounds from %s: '), $character->Name);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal 1 Wound'), 'heal1Wound');

        $owner = $this->getOwningCharacter($theah);
        $id = $this->HealTargetIds[0];
        $character = $theah->getCharacterById($id);
        if ($owner->hasTrait("Strega") && $character->Wounds > 1)
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal 2 Wounds'), 'heal2Wounds');    

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterWounded && $this->ownerIsAttached($event->theah) && $this->isAvailable())
        {
            $attachment = $this->getOwningCard($event->theah);
            $character = $event->theah->getCardById($event->characterId);
            if ($character->Location == $attachment->Location && ! $attachment->Engaged) 
            {
                $this->HealTargetIds[] =  $event->characterId;
                $attachment->IsUpdated = true;

                $transition = EventFactory::createReactionTransitionEvent($attachment->ControllerId, $attachment->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }   
        }

    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "heal1Wound")
        {
            $this->healWound($game, 1);
        }

        if ($reactionId == "heal2Wounds")
        {
            $this->healWound($game, 2);
        }

        if ($reactionId == "pass")
        {
            //Remove the first target id from the array
            array_shift($this->HealTargetIds);
            $attachment = $this->getOwningCard($game->theah);
            $game->theah->addCardToWorld($attachment);
            $game->updateCardObjectInDb($attachment);
        }

        $game->gamestate->nextState("done");
    }
    
    private function healWound(Game $game, int $wounds): void
    {
        $this->setUsed($game->theah, true);

        $attachment = $this->getOwningCard($game->theah);

        //Pop the first target id from the array.  The rest are removed.
        $id = array_shift($this->HealTargetIds);
        $this->HealTargetIds = [];
        $game->theah->addCardToWorld($attachment);
        $game->updateCardObjectInDb($attachment);

        //Delete any remaining transition events for this reaction
        $game->theah->deleteTransitionEvents($this->Id);

        $engageEvent = EventFactory::createCardEngagedEvent($attachment->ControllerId, $attachment->Id, $attachment->Id, $this->Id);
        $game->theah->queueEvent($engageEvent);

        $healedEvent = EventFactory::createCharacterHealedEvent($id, $attachment->Id, $wounds, $attachment->getInjectCode());
        $game->theah->queueEvent($healedEvent);
    }
}