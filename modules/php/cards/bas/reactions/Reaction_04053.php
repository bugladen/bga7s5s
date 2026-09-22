<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04053 extends AttachmentReaction
{
    // WHY: Cancel-first like Cascade Reaction_02059 — clone the wound, offer Ignore/Pass.
    // If Pass, re-queue the clone; skipNextEvent prevents re-offering on that re-queue.
    private ?EventCharacterBeingWounded $savedWoundEvent = null;
    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ignore Opponent's Wound");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah)
            . $theah->game->translate('${you} may engage this card to ignore a wound from an opponent\'s ability: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Ignore Wound'), 'ignoreWound');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCharacterBeingWounded) || $event->canceled)
        {
            return;
        }

        if (! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        if ($this->savedWoundEvent !== null)
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner === null || $owner->Engaged)
        {
            return;
        }

        if ($this->skipNextEvent)
        {
            $this->skipNextEvent = false;
            $owner->IsUpdated = true;
            return;
        }

        $owningCharacter = $this->getOwningCharacter($event->theah);
        if ($owningCharacter === null || $event->characterId != $owningCharacter->Id)
        {
            return;
        }

        // WHY: Mirror Cascade (02059) — only wounds sourced from an opponent's ability.
        // Empty abilityId / missing ability = non-ability wound (e.g. duel threat resolve).
        if ($event->abilityId === '')
        {
            return;
        }

        $source = $event->theah->getCardById($event->sourceId);
        if ($source === null)
        {
            return;
        }

        $ability = $source->getAbilityById($event->abilityId);
        if ($ability === null)
        {
            return;
        }

        $abilityOwner = $ability->getOwningCard($event->theah);
        if ($abilityOwner === null || $abilityOwner->ControllerId == $owner->ControllerId)
        {
            return;
        }

        $this->savedWoundEvent = clone $event;
        unset($this->savedWoundEvent->theah);
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningAttachment($game->theah);

        if ($reactionId === 'ignoreWound')
        {
            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} engaged this card to ignore the wound.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $this->savedWoundEvent = null;
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
        }

        if ($reactionId === 'pass')
        {
            if ($this->savedWoundEvent !== null)
            {
                $game->theah->queueEvent($this->savedWoundEvent);
                $this->savedWoundEvent = null;
                $this->skipNextEvent = true;
            }
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
