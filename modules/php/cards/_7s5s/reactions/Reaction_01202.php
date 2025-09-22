<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01202 extends AttachmentReaction
{
    private int $SavedCharacterId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Put Character into Approach Deck');
        $this->SavedCharacterId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        $character = $theah->getCardById($this->SavedCharacterId);
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may choose to save %s and put them into your Approach Deck: '), $character->Name);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Put Character into Approach Deck'), 'saveCharacter');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed && $this->ownerIsAttached($event->theah) && $this->isAvailable())
        {
            $attachment = $this->getOwningAttachment($event->theah);
            if ($attachment->isAttached())
            {
                $owningCharacter = $this->getOwningCharacter($event->theah);
                $dyingCharacter = $event->theah->getCharacterById($event->characterId);
                if ($owningCharacter->ControllerId == $dyingCharacter->ControllerId && ! $dyingCharacter instanceof Leader && ! $dyingCharacter->hasTrait("Mercenary"))
                {
                    $this->SavedCharacterId = $dyingCharacter->Id;
                    $objectOfWonder = $this->getOwningCard($event->theah);
                    $objectOfWonder->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($attachment->ControllerId, $attachment->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            } 
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'saveCharacter')
        {
            $playerId = $game->getActivePlayerId();
            $approachDeckEvent = EventFactory::createCharacterPutIntoApproachDeckEvent($playerId, $this->SavedCharacterId);
            $game->theah->eventCheck($approachDeckEvent);

            $attachment = $this->getOwningAttachment($game->theah);
            $owningCharacter = $this->getOwningCharacter($game->theah);
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($playerId, $owningCharacter->Id, $attachment->Id);
            $game->theah->eventCheck($unequipEvent);

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->ControllerId, $attachment->Id, $attachment->Location);
            $game->theah->eventCheck($discardEvent);

            $owner = $this->getOwningCard($game->theah);
            $targetCharacter = $game->theah->getCharacterById($this->SavedCharacterId);
            $game->notifyAllPlayers('message', clienttranslate('${owner_inject_code}: ${player_name} used Reaction to put ${character_inject_code} into their Approach Deck.'), [
                'owner_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getActivePlayerName(),
                'character_inject_code' => $targetCharacter->getInjectCode(),
            ]);

            $game->theah->queueEvent($approachDeckEvent);
            $game->theah->queueEvent($unequipEvent);
            $game->theah->queueEvent($discardEvent);
        }

        $game->gamestate->nextState("done");
    }

}
