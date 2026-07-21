<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03031 extends RiskReaction
{
    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;
    private ?EventCharacterIntervened $characterIntervenedEvent = null;

    private bool $skipNextEvent = false;

    private ?string $savedAbilityId = null;
    private ?int $savedSourceId = null;
    private ?int $targetCharacterId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Redirect Opponent Ability to Performer at Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may redirect the ability to your performer at this location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $targetCharacter = $theah->getCharacterById($this->targetCharacterId);
        if ($targetCharacter === null)
        {
            return $array;
        }

        $performers = $theah->getCharactersAtLocationByPlayerId($targetCharacter->Location, $owner->ControllerId);
        $performers = array_filter($performers, fn($character) => $character->Id != $this->targetCharacterId);
        foreach ($performers as $performer)
        {
            $array[] = $this->createButtonProperty($theah->game, $performer->Name, "redirect-$performer->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, "Decline", "decline");

        return $array;
    }

    private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId, ?int $targetCharacterId = null): bool
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return false;
        }

        if (! $this->isOpponentAbility($theah, $sourceId, $abilityId, $owner->ControllerId))
        {
            return false;
        }

        $targetCharacter = $theah->getCharacterById($targetCharacterId);
        if ($targetCharacter === null || $targetCharacter->ControllerId != $owner->ControllerId)
        {
            return false;
        }

        $performersAtLocation = $theah->getCharactersAtLocationByPlayerId($targetCharacter->Location, $owner->ControllerId);
        $performersAtLocation = array_filter($performersAtLocation, fn($character) => $character->Id != $targetCharacter->Id);
        if (count($performersAtLocation) == 0)
        {
            return false;
        }

        $this->savedAbilityId = $abilityId;
        $this->savedSourceId = $sourceId;
        return true;
    }

    private function isOpponentAbility(Theah $theah, int $sourceId, string $abilityId, int $ownerPlayerId): bool
    {
        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            return $source->ControllerId != $ownerPlayerId && $source->ControllerId != 0;
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action && $action instanceof ICardAbility)
        {
            $owningCard = $action->getOwningCard($theah);
            return $owningCard !== null && $owningCard->ControllerId != $ownerPlayerId && $owningCard->ControllerId != 0;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngaged && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || ! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

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

        if ($event instanceof EventCardMoving && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || ! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

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

        if ($event instanceof EventCharacterBeingWounded && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || ! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

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

        if ($event instanceof EventCharacterIntervened && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || ! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            $intervener = $event->theah->getCharacterById($event->newTargetId);
            if ($intervener === null || $intervener->ControllerId != $owner->ControllerId)
            {
                return;
            }

            if ($this->skipNextEvent)
            {
                $this->skipNextEvent = false;
                $owner->IsUpdated = true;
                return;
            }

            $performersAtLocation = $event->theah->getCharactersAtLocationByPlayerId($intervener->Location, $owner->ControllerId);
            $performersAtLocation = array_filter($performersAtLocation, fn($character) => $character->Id != $intervener->Id);
            if (count($performersAtLocation) > 0)
            {
                $this->characterIntervenedEvent = clone $event;
                unset($this->characterIntervenedEvent->theah);
                $this->targetCharacterId = $intervener->Id;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId != 'decline')
            {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($event->theah);
                $characterId = (int) str_replace("redirect-", "", $event->reactionId);
                $character = $game->theah->getCharacterById($characterId);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to redirect the ability to ${character_inject_code}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

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
                else
                {
                    // WHY: Card text is effect-based ("would wound, move, or engage"), not "targets".
                    // Non-targeting abilities still emit these events; redirect without isValidTargetForAbility.
                    $this->releaseEvent($game, $characterId);
                }

                $this->setUsed($game->theah, true);
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

        if ($this->characterIntervenedEvent)
        {
            $intervener = $game->theah->getCharacterById($this->targetCharacterId);
            $intervener->removeCondition(Game::DUEL_DEFENDER);

            $performer = $game->theah->getCharacterById($characterId);
            $performer->addCondition(Game::DUEL_DEFENDER);

            $game->globals->set(Game::CHOSEN_TARGET, $performer->Id);

            $this->characterIntervenedEvent->oldTargetId = $intervener->Id;
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
        $this->cardMovingEvent = null;
        $this->characterWoundedEvent = null;
        $this->characterIntervenedEvent = null;
        $game->globals->set(Game::CHALLENGE_CANCELLED, true);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($payEvent);

            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($payTransition);
        }
        else if ($this->characterIntervenedEvent)
        {
            $this->releaseEvent($game, $this->targetCharacterId);
            $this->skipNextEvent = true;
        }

        $game->gamestate->nextState('done');
    }
}
