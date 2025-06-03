<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01181 extends AttachmentReaction
{
    public int $HealTargetId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Heal Wounds';

        $this->HealTargetId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Heal Wounds: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, 'Heal 1 Wound', 'heal1Wound');

        $owner = $this->getOwningCharacter($theah);
        if ($owner instanceof Character)
        {
            if ($owner && in_array("Strega", $owner->Traits) && $owner->Wounds > 1)
                $array[] = $this->createButtonProperty($theah->game, 'Heal 2 Wounds', 'heal2Wounds');    
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        $attachment = $this->getOwningCard($event->theah);
        if ($event instanceof EventCharacterWounded && $this->ownerIsAttached($event->theah) && $this->isAvailable() && ! $attachment->Engaged)
        {
            $character = $event->theah->getCardById($event->characterId);
            if ($character->Location == $attachment->Location) 
            {
                $this->HealTargetId =  $event->characterId;
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

        $game->gamestate->nextState("done");
    }
    
    private function healWound(Game $game, int $wounds): void
    {
        $this->setUsed($game->theah, true);

        $attachment = $this->getOwningCard($game->theah);
        $attachment->IsUpdated = true;

        $engageEvent = EventFactory::createCardEngagedEvent($attachment->ControllerId, $attachment->Id);
        $game->theah->queueEvent($engageEvent);

        $healedEvent = EventFactory::createCharacterHealedEvent($this->HealTargetId, $attachment->Id, $wounds, $game->translate("Sorte Deck"));
        $game->theah->queueEvent($healedEvent);
    }
}