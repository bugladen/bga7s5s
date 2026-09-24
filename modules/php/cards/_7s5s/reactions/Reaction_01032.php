<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01032 extends RiskReaction
{
    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardEngarded $engardedEvent = null;
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;
    private ?EventCharacterBeingHealed $characterHealedEvent = null;
    private ?EventChallengeIssued $challengeIssuedEvent = null;
    private ?EventCharacterTargeted $characterTargetedEvent = null;

    // '' = offer Play/Pass (before wealth). 'cost' = destroy Red Hand / discard Thug (after pay).
    private string $stage = '';
    private bool $inPlayRedHand = false;
    private bool $inHandThug = false;
    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Targeted Ability on one of your Characters");
    }

    public function getReactionDescription(Theah $theah): string
    {
        if ($this->stage === 'cost')
        {
            $message = '';
            if ($this->inPlayRedHand && $this->inHandThug)
            {
                $message = $theah->game->translate('${you} must destroy an In-Play Red Hand or discard a Thug from your Hand: ');
            }
            else if ($this->inHandThug)
            {
                $message = $theah->game->translate('${you} must choose a Thug in your Hand to discard: ');
            }
            else if ($this->inPlayRedHand)
            {
                $message = $theah->game->translate('${you} must choose an In-Play Red Hand to destroy: ');
            }

            return parent::getReactionDescription($theah) . $message;
        }

        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may play this Risk to cancel the targeted ability: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->stage === 'cost')
        {
            $owner = $this->getOwningCard($theah);

            if ($this->inPlayRedHand)
            {
                $cards = $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
                $cards = array_filter($cards, fn($card) => $card->hasTrait("Red Hand"));
                foreach ($cards as $card)
                {
                    $array[] = $this->createButtonProperty($theah->game, $card->Name, "destroy-$card->Id");
                }
            }

            if ($this->inHandThug)
            {
                $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
                $cards = array_filter($cards, fn($card) => $card->hasTrait("Thug"));
                foreach ($cards as $card)
                {
                    $array[] = $this->createButtonProperty($theah->game, $card->Name, "discard-$card->Id");
                }
            }

            return $array;
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Unyielding Loyalty'), 'use');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    // $initiatingPlayerId: fallback when sourceId is 0 (e.g. BasicChallenge).
    private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId, int $initiatingPlayerId = 0): bool
    {
        $owner = $this->getOwningCard($theah);
        $source = $theah->getCardById($sourceId);

        // WHY: Cancel is for opposing targeting, not your own costs/self-effects.
        // Cirilo recruit (Action_01009) is IAbilityThatTargetsCards and engages Cirilo
        // himself — that was firing Unyielding Loyalty during your own Recruit.
        $initiatorId = $source ? $source->ControllerId : $initiatingPlayerId;
        if ($initiatorId === 0 || $initiatorId === $owner->ControllerId)
        {
            return false;
        }

        if ($source)
        {
            $ability = $source->getAbilityById($abilityId);
            if ($ability instanceof IAbilityThatTargetsCharacters || $ability instanceof IAbilityThatTargetsCards)
            {
                return true;
            }
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action instanceof IAbilityThatTargetsCharacters || $action instanceof IAbilityThatTargetsCards)
        {
            return true;
        }

        return false;
    }
    
    private function thugsInHand(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        $hand = array_filter($hand, fn($card) => $card->hasTrait("Thug"));
        return count($hand) > 0;
    }

    private function redHandsInPlay(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Red Hand"));
        return count($characters) > 0;
    }

    // WHY: Wealth is paid first (Play → pay state). Additional cost (Red Hand / Thug)
    // is a second playerReaction after EventRiskReactionTriggered. Do not setUsed
    // before that second transition — Theah skips reaction transitions when
    // ! isAvailable().
    private function interceptEvent(Event $event, string $property): void
    {
        $owner = $this->getOwningCard($event->theah);

        if ($this->skipNextEvent)
        {
            $this->skipNextEvent = false;
            $owner->IsUpdated = true;
            return;
        }

        $hasRedHand = $this->redHandsInPlay($event->theah);
        $hasThug = $this->thugsInHand($event->theah);
        if (! $hasRedHand && ! $hasThug)
        {
            return;
        }

        $clone = clone $event;
        unset($clone->theah);
        $this->$property = $clone;
        $this->inPlayRedHand = $hasRedHand;
        $this->inHandThug = $hasThug;
        $this->stage = '';
        $owner->IsUpdated = true;

        $event->canceled = true;

        if ($event->batchId)
        {
            $event->theah->deleteEventBatch($event->batchId);
        }

        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($reactionTransitionEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngaged && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $card = $event->theah->getCardById($event->cardId);
                // WHY: Card text is "your cards" — attachments engage too (Henri, Yield, etc.).
                if ($card !== null &&
                    $owner->ControllerId == $card->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->playerId))
                {
                    $this->interceptEvent($event, 'engagedEvent');
                }
            }
        }

        if ($event instanceof EventCardEngarded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                // WHY: getCharacterById is null for attachments. Dusk cleanup and several
                // abilities engarde attachments; that was a fatal on ControllerId.
                $card = $event->theah->getCardById($event->cardId);
                if ($card !== null &&
                    $owner->ControllerId == $card->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->playerId))
                {
                    $this->interceptEvent($event, 'engardedEvent');
                }
            }
        }

        if ($event instanceof EventCardMoving && $this->isAvailable() && !$event->canceled)
        {   
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $card = $event->theah->getCardById($event->cardId);
                if ($card !== null &&
                    $owner->ControllerId == $card->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->initiatingPlayerId))
                {
                    $this->interceptEvent($event, 'cardMovingEvent');
                }
            }
        }

        if ($event instanceof EventCharacterBeingWounded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->characterId);
                if ($character !== null &&
                    $owner->ControllerId == $character->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    $this->interceptEvent($event, 'characterWoundedEvent');
                }
            }
        }

        if ($event instanceof EventCharacterBeingHealed && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->characterId);
                if ($character !== null &&
                    $owner->ControllerId == $character->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    $this->interceptEvent($event, 'characterHealedEvent');
                }
            }
        }

        if ($event instanceof EventCharacterTargeted && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->targetId);
                if ($character !== null &&
                    $owner->ControllerId == $character->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->playerId))
                {
                    $this->interceptEvent($event, 'characterTargetedEvent');
                }
            }
        }

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $defender = $event->theah->getCharacterById($event->defenderId);
                $challenger = $event->theah->getCharacterById($event->challengerId);
                if ($defender !== null && $challenger !== null &&
                    ($owner->ControllerId == $defender->ControllerId || $owner->ControllerId == $challenger->ControllerId) &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->playerId))
                {
                    $this->interceptEvent($event, 'challengeIssuedEvent');
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $this->beginCostChoice($event->theah);
        }
    }

    // After wealth is paid (or Night of Drinking cancelled the Risk), collect the
    // additional cost. Recompute live — a Thug may have been discarded as payment.
    private function beginCostChoice(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return;
        }

        $this->inPlayRedHand = $this->redHandsInPlay($theah);
        $this->inHandThug = $this->thugsInHand($theah);

        if (! $this->inPlayRedHand && ! $this->inHandThug)
        {
            $theah->game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} has no Red Hand in play or Thug in hand left to pay the additional cost. The cancel does not resolve.'), [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $theah->game->getPlayerNameById($owner->ControllerId),
            ]);
            $this->releaseEvent($theah->game);
            $this->finalize($theah);
            return;
        }

        $this->stage = 'cost';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function payCost(Game $game, string $reactionId): void
    {
        $owner = $this->getOwningCard($game->theah);

        if (str_starts_with($reactionId, 'discard-'))
        {
            $characterId = (int) str_replace('discard-', '', $reactionId);
            $character = $game->theah->getCardById($characterId);
            if ($character === null || $character->Location != Game::LOCATION_HAND || $character->ControllerId != $owner->ControllerId || ! $character->hasTrait('Thug'))
            {
                throw new UserException($game->translate('Invalid Thug selection.'));
            }

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($owner->ControllerId, $characterId, $owner->Id);
            $game->theah->queueEvent($discardEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to discard ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);
            return;
        }

        if (str_starts_with($reactionId, 'destroy-'))
        {
            $characterId = (int) str_replace('destroy-', '', $reactionId);
            $character = $game->theah->getCardById($characterId);
            if ($character === null || $character->ControllerId != $owner->ControllerId || ! $character->hasTrait('Red Hand'))
            {
                throw new UserException($game->translate('Invalid Red Hand selection.'));
            }

            $destroyEvent = EventFactory::createCharacterDestroyedEvent($character->ControllerId, $characterId, $character->Location);
            $game->theah->queueEvent($destroyEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to destroy ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);
            return;
        }

        throw new UserException($game->translate('Invalid choice.'));
    }

    private function clearEvents(Game $game)
    {
        $this->engagedEvent = null;
        $this->engardedEvent = null;
        $this->cardMovingEvent = null;
        $this->characterWoundedEvent = null;
        $this->characterHealedEvent = null;
        $this->characterTargetedEvent = null;

        if ($this->challengeIssuedEvent != null)
        {
            $game->globals->set(Game::CHALLENGE_CANCELLED, true);
        }
        $this->challengeIssuedEvent = null;
    }

    private function finalize(Theah $theah): void
    {
        $this->setUsed($theah, true);
        $this->stage = '';
        $this->inHandThug = false;
        $this->inPlayRedHand = false;

        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function revertCancellation(Theah $theah): void
    {
        $game = $theah->game;
        $this->releaseEvent($game);

        // WHY: Night of Drinking (01109) deletes EventRiskReactionTriggered, so the
        // normal post-pay cost choice never starts. 01109 says all costs are still
        // paid — queue the Red Hand / Thug choice here after restoring the effect.
        $this->beginCostChoice($theah);
    }

    private function releaseEvent(Game $game)
    {
        if ($this->engagedEvent)
        {
            $game->theah->queueEvent($this->engagedEvent);
            $this->engagedEvent = null;
        }

        if ($this->engardedEvent)
        {
            $game->theah->queueEvent($this->engardedEvent);
            $this->engardedEvent = null;
        }

        if ($this->cardMovingEvent)
        {
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
        }
        
        if ($this->characterWoundedEvent)
        {
            $game->theah->queueEvent($this->characterWoundedEvent);
            $this->characterWoundedEvent = null;
        }
        
        if ($this->characterHealedEvent)
        {
            $game->theah->queueEvent($this->characterHealedEvent);
            $this->characterHealedEvent = null;
        }

        if ($this->characterTargetedEvent)
        {
            $game->theah->queueEvent($this->characterTargetedEvent);
            $this->characterTargetedEvent = null;
        }

        if ($this->challengeIssuedEvent)
        {
            $game->theah->queueEvent($this->challengeIssuedEvent);
            $this->challengeIssuedEvent = null;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === 'cost')
        {
            $this->payCost($game, $reactionId);
            $this->clearEvents($game);
            $this->finalize($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        if ($reactionId == 'use')
        {
            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        if ($reactionId == 'pass')
        {
            $this->releaseEvent($game);
            $this->skipNextEvent = true;
            $this->stage = '';
            $this->inHandThug = false;
            $this->inPlayRedHand = false;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState('done');
    }
}
