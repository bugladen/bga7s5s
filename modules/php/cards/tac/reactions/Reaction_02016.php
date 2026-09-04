<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02016 extends AttachmentReaction
{
    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardEngarded $engardedEvent = null;
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;
    private ?EventCharacterBeingHealed $characterHealedEvent = null;
    private ?EventChallengeIssued $challengeIssuedEvent = null;
    private ?EventCharacterIntervened $characterIntervenedEvent = null;
    private ?EventCharacterTargeted $characterTargetedEvent = null;
    private bool $isChallenger = false;
    private ?string $savedAbilityId = null;
    private ?int $savedSourceId = null;
    private ?int $targetCharacterId = null;

    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Redirect Targeted Ability to Performer at Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may wound your performer to become the new target: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        // WHY: Card text redirects only to the equipped character ("your performer"),
        // not any ally at the location. Prior code listed every character at the location.
        $owningCharacter = $this->getOwningCharacter($theah);
        if ($owningCharacter && $owningCharacter->Id != $this->targetCharacterId)
        {
            $array[] = $this->createButtonProperty($theah->game, $owningCharacter->Name, "redirect-$owningCharacter->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, "Decline", "decline");

        return $array;
    }

    private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId, ?int $targetCharacterId = null): bool
    {
        if (! $this->ownerIsAttached($theah))
        {
            return false;
        }

        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            $ability = $source->getAbilityById($abilityId);
            if (! $ability instanceof IAbilityThatTargetsCharacters)
            {
                return false;
            }
        }
        else
        {
            return false;
        }

        $owner = $this->getOwningAttachment($theah);
        if ($owner && ! $owner->isAttached())
        {
            return false;
        }

        $owningCharacter = $this->getOwningCharacter($theah);

        if ($source->ControllerId == $owningCharacter->ControllerId)
        {
            return false;
        }

        $targetCharacter = $theah->getCharacterById($targetCharacterId);

        // Target character must be controlled by the same player as the owning character
        if ($targetCharacter && $targetCharacter->ControllerId != $owningCharacter->ControllerId)
        {
            return false;
        }

        if ($targetCharacter && $targetCharacter->Location != $owningCharacter->Location)
        {
            return false;
        }

        // WHY: Redirect target is always the equipped character. No point reacting when
        // they are already the target — there is no "instead" to apply.
        if ($targetCharacter && $targetCharacter->Id == $owningCharacter->Id)
        {
            return false;
        }

        $this->savedAbilityId = $abilityId;
        $this->savedSourceId = $sourceId;
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngaged && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->cardId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->engagedEvent = clone $event;
                unset($this->engagedEvent->theah);
                $this->targetCharacterId = $event->cardId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventCardEngarded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->cardId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->engardedEvent = clone $event;
                unset($this->engardedEvent->theah);
                $this->targetCharacterId = $event->cardId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventCardMoving && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->cardId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->cardMovingEvent = clone $event;
                unset($this->cardMovingEvent->theah);
                $this->targetCharacterId = $event->cardId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }


        if ($event instanceof EventCharacterBeingWounded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->characterId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->characterWoundedEvent = clone $event;
                unset($this->characterWoundedEvent->theah);
                $this->targetCharacterId = $event->characterId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventCharacterTargeted && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->targetId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->characterTargetedEvent = clone $event;
                unset($this->characterTargetedEvent->theah);
                $this->targetCharacterId = $event->targetId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventCharacterBeingHealed && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->characterId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->characterHealedEvent = clone $event;
                unset($this->characterHealedEvent->theah);
                $this->targetCharacterId = $event->characterId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningAttachment($event->theah);

            if (! $this->ownerIsAttached($event->theah))
            {
                return false;
            }

            $owningCharacter = $this->getOwningCharacter($event->theah);
            $challenger = $event->theah->getCharacterById($event->challengerId);
            $defender = $event->theah->getCharacterById($event->defenderId);

            if ($challenger->ControllerId != $owningCharacter->ControllerId && $defender->ControllerId != $owningCharacter->ControllerId)
            {
                return;
            }            

            $participant = $challenger->ControllerId == $owningCharacter->ControllerId ? $challenger : $defender;
            
            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $participant->Id))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->challengeIssuedEvent = clone $event;
                unset($this->challengeIssuedEvent->theah);
                $this->isChallenger = $participant->Id == $event->challengerId;
                $this->targetCharacterId = $participant->Id;
                $owner->IsUpdated = true;
                $event->canceled = true;


                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventCharacterIntervened && $this->isAvailable() && !$event->canceled)
        {
            if (! $this->ownerIsAttached($event->theah))
            {
                return;
            }

            $owner = $this->getOwningAttachment($event->theah);
            $owningCharacter = $this->getOwningCharacter($event->theah);
            $newTarget = $event->theah->getCharacterById($event->newTargetId);

            // WHY: Card redirects TO the equipped character. Trigger when an ally at this
            // location (not the performer) becomes the intervention target — opposite of
            // Vittoria (01014), which redirects away from herself onto a Thug.
            if ($newTarget
                && $newTarget->ControllerId == $owningCharacter->ControllerId
                && $newTarget->Location == $owningCharacter->Location
                && $newTarget->Id != $owningCharacter->Id)
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->characterIntervenedEvent = clone $event;
                unset($this->characterIntervenedEvent->theah);
                $this->targetCharacterId = $newTarget->Id;
                $owner->IsUpdated = true;

                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }
    }

    private function releaseEvent(Game $game, int $characterId)
    {
        if ($this->engagedEvent)
        {
            $this->engagedEvent->cardId = $characterId;
            $game->theah->queueEvent($this->engagedEvent);
            $this->engagedEvent = null;
        }

        if ($this->engardedEvent)
        {
            $this->engardedEvent->cardId = $characterId;
            $game->theah->queueEvent($this->engardedEvent);
            $this->engardedEvent = null;
        }

        if ($this->cardMovingEvent)
        {
            $this->cardMovingEvent->cardId = $characterId;
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
        }
        
        if ($this->characterWoundedEvent)
        {
            $this->characterWoundedEvent->characterId = $characterId;
            $game->theah->queueEvent($this->characterWoundedEvent);
            $this->characterWoundedEvent = null;
        }
        
        if ($this->characterHealedEvent)
        {
            $this->characterHealedEvent->characterId = $characterId;
            $game->theah->queueEvent($this->characterHealedEvent);
            $this->characterHealedEvent = null;
        }

        if ($this->characterTargetedEvent)
        {
            $this->characterTargetedEvent->targetId = $characterId;
            $game->theah->queueEvent($this->characterTargetedEvent);
            $this->characterTargetedEvent = null;
        }

        if ($this->challengeIssuedEvent)
        {
            if ($this->isChallenger)
            {
                $game->globals->set(GAME::CHOSEN_PERFORMER, $characterId);
                $this->challengeIssuedEvent->challengerId = $characterId;
            }
            else
            {
                $game->globals->set(GAME::CHOSEN_TARGET, $characterId);
                $this->challengeIssuedEvent->defenderId = $characterId;
            }
            $game->theah->queueEvent($this->challengeIssuedEvent);
            $this->isChallenger = false;
            $this->challengeIssuedEvent = null;
        }

        if ($this->characterIntervenedEvent)
        {
            $originalTarget = $game->theah->getCharacterById($this->targetCharacterId);
            if ($originalTarget)
            {
                $originalTarget->removeCondition(Game::DUEL_DEFENDER);
            }

            $performer = $game->theah->getCharacterById($characterId);
            $performer->addCondition(Game::DUEL_DEFENDER);

            $game->globals->set(GAME::CHOSEN_TARGET, $performer->Id);

            // WHY: Intervention was on an ally; redirect sets performer as newTarget.
            $this->characterIntervenedEvent->oldTargetId = $this->targetCharacterId;
            $this->characterIntervenedEvent->newTargetId = $performer->Id;
            $game->theah->queueEvent($this->characterIntervenedEvent);
            $this->characterIntervenedEvent = null;
        }
    }

    private function loadAbility(Theah $theah): ?IAbilityThatTargetsCharacters
    {
        if ($this->savedSourceId !== null && $this->savedAbilityId !== null)
        {
            $source = $theah->getCardById($this->savedSourceId);
            if ($source)
            {
                $ability = $source->getAbilityById($this->savedAbilityId);
                if ($ability instanceof IAbilityThatTargetsCharacters)
                {
                    return $ability;
                }
            }

            $action = $theah->getInPlayActionById($this->savedAbilityId);
            if ($action instanceof IAbilityThatTargetsCharacters)
            {
                return $action;
            }
        }
        return null;
    }

    private function cancelEvents(Game $game): void
    {
        $this->engagedEvent = null;
        $this->engardedEvent = null;
        $this->cardMovingEvent = null;
        $this->characterWoundedEvent = null;
        $this->characterHealedEvent = null;
        $this->characterTargetedEvent = null;
        $this->isChallenger = false;
        $this->challengeIssuedEvent = null;
        $this->characterIntervenedEvent = null;
        $game->globals->set(Game::CHALLENGE_CANCELLED, true);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            $owningCharacter = $this->getOwningCharacter($game->theah);
            $characterId = (int) str_replace("redirect-", "", $reactionId);
            $character = $game->theah->getCharacterById($characterId);

            // WHY: Only the equipped character may become the new target.
            if (! $owningCharacter || $characterId != $owningCharacter->Id)
            {
                throw new UserException($game->translate("Only the equipped character may become the new target."));
            }

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to redirect the ability to ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $ability = $this->loadAbility($game->theah);
            if ($ability)
            {
                [$isValid, ] = $ability->isValidTargetForAbility($game, $character);
                if ($isValid)
                {
                    $this->releaseEvent($game, $characterId);
                }
                else
                {
                    $game->notify->all("message", clienttranslate('${character_inject_code} is not a valid target for the ability. The ability has been canceled.'), [
                        "character_inject_code" => $character->getInjectCode(),
                    ]);
                    $this->cancelEvents($game);
                }
            }
            else if ($this->characterIntervenedEvent)
            {
                $this->releaseEvent($game, $characterId);
            }

            $this->setUsed($game->theah, true);
        }
        else if ($this->characterIntervenedEvent)
        {
            // WHY: DUEL_DEFENDER was already swapped onto the intervener in actHighDramaChallengeActionIntervene
            // before this event fired. Decline only needs to re-emit the canceled notify event as-is.
            // releaseEvent would clobber oldTargetId. skipNextEvent prevents re-trigger on the same ally.
            $game->theah->queueEvent($this->characterIntervenedEvent);
            $this->characterIntervenedEvent = null;
            $this->skipNextEvent = true;
            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState('done');
    }
}