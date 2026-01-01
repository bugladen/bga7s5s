<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01100 extends AttachmentReaction
{
    public bool $IsActivated;
    public int $AdversaryId;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Adversary Reveals One Less Card when Gambling");
        $this->IsActivated = false;
        $this->AdversaryId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . clienttranslate('${you} may choose to have the Adversary Reveal One Less Card when Gambling: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Activate'), 'activate');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeAccepted && $this->ownerIsAttached($event->theah) && $this->isAvailable())
        {
            $challenger = $event->theah->getCardById($event->challengerId);
            $target = $event->theah->getCardById($event->targetId);
            $owner = $this->getOwningAttachment($event->theah);
            if ($challenger->ControllerId == $owner->ControllerId && $challenger->Location == $owner->Location)
            {
                $this->AdversaryId = $event->targetId;
                $this->IsActivated = true;
                $owner->IsUpdated = true;

                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
            else if ($target->ControllerId == $owner->ControllerId && $target->Location == $owner->Location)
            {
                $this->AdversaryId = $event->challengerId;
                $this->IsActivated = true;
                $owner->IsUpdated = true;

                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }

        if ($event instanceof EventCharacterIntervened && $this->ownerIsAttached($event->theah) && $this->isAvailable())
        {
            $challengerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $challenger = $event->theah->getCharacterById($challengerId);
            $target = $event->theah->getCharacterById($event->newTargetId);
            $owner = $this->getOwningAttachment($event->theah);
            if ($challenger->ControllerId == $owner->ControllerId && $challenger->Location == $owner->Location)
            {
                $this->AdversaryId = $event->newTargetId;
                $this->IsActivated = true;
                $owner->IsUpdated = true;

                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
            else if ($target->ControllerId == $owner->ControllerId && $target->Location == $owner->Location)
            {
                $this->AdversaryId = $challengerId;
                $this->IsActivated = true;
                $owner->IsUpdated = true;

                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }            
        }

        if ($event instanceof EventDuelEnd)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->AdversaryId = 0;
            $this->IsActivated = false;
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'activate')
        {
            $owner = $this->getOwningCard($game->theah);
            $this->IsActivated = true;
            $owner->IsUpdated = true;

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id);
            $game->theah->queueEvent($engageEvent);
        }

        $game->gamestate->nextState("done");
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int
    {
        if ($this->IsActivated && $actor->Id == $this->AdversaryId)
        {
            $owner = $this->getOwningCard($theah);
            $owningCharacter = $this->getOwningCharacter($theah);

            if ($owningCharacter->Location == $actor->Location)
            {
                $explanations[] = sprintf($theah->game->translate("%s: -1 for being the adversary and at the same location."), $owner->getInjectCode());
                return -1;
            }
        }

        return 0;
    }
}