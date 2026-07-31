<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02033 extends CardReaction
{
    private int $pendingMoverCharacterId = 0;

    private bool $awaitingMoverDiscard = false;

    // WHY: Abilities like Blood Mark (01076) queue multiple EventCardMoved with the same
    // abilityId. Rosa must offer at most one discard prompt per ability resolution — not
    // once per arriving character. pendingMoverCharacterId only covers the case where both
    // Moved events run before the reaction UI; consumedAbilityId also blocks later moves
    // from that same ability after pending is cleared (e.g. After-reaction sequencing).
    private string $consumedAbilityId = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Player discards a card after moving to Rosa's location");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay) {
            $this->pendingMoverCharacterId = 0;
            $this->awaitingMoverDiscard = false;
            $this->consumedAbilityId = '';
            // WHY: parent::handleEvent already ran setUsed→updateCardObjectInDb with the
            // old flags. Mark the owner dirty again so the cleared flags are written.
            $rosa = $this->getOwningCharacter($event->theah);
            if ($rosa !== null) {
                $rosa->IsUpdated = true;
            }
        }

        // WHY: Clear after the action finishes so a later use of a Repeatable ability
        // (same abilityId string) can trigger Rosa again the same day.
        if ($event instanceof EventActionResolved && $this->consumedAbilityId !== '') {
            $this->consumedAbilityId = '';
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null) {
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventCardMoved && $this->isAvailable()) {
            $rosa = $this->getOwningCharacter($event->theah);
            if ($rosa === null || ! $event->theah->cardInCity($rosa)) {
                return;
            }

            $moved = $event->theah->getCardById($event->cardId);
            if (! $moved instanceof Character || $moved->Id == $rosa->Id) {
                return;
            }

            if ($event->toLocation != $rosa->Location) {
                return;
            }

            if ($this->pendingMoverCharacterId != 0) {
                return;
            }

            if ($event->abilityId !== '' && $event->abilityId === $this->consumedAbilityId) {
                return;
            }

            $this->pendingMoverCharacterId = $event->cardId;
            if ($event->abilityId !== '') {
                $this->consumedAbilityId = $event->abilityId;
            }
            $rosa->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($rosa->ControllerId, $rosa->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getReactionDescription(Theah $theah): string
    {
        $game = $theah->game;
        $rosa = $this->getOwningCharacter($theah);
        $mover = $this->pendingMoverCharacterId ? $theah->getCharacterById($this->pendingMoverCharacterId) : null;

        if ($this->awaitingMoverDiscard && $mover && (int) $game->getActivePlayerId() === $mover->ControllerId) {
            return parent::getReactionDescription($theah) . $game->translate('${you} must discard a card from your hand: ');
        }

        if (! $this->awaitingMoverDiscard && $rosa && (int) $game->getActivePlayerId() === $rosa->ControllerId) {
            return parent::getReactionDescription($theah) . $game->translate('${you} may have their controller discard a card, or pass: ');
        }

        return parent::getReactionDescription($theah);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $game = $theah->game;
        $activeId = (int) $game->getActivePlayerId();
        $rosa = $this->getOwningCharacter($theah);
        if ($rosa === null) {
            return [];
        }

        $mover = $this->pendingMoverCharacterId ? $theah->getCharacterById($this->pendingMoverCharacterId) : null;

        if ($this->awaitingMoverDiscard && $mover && $activeId === $mover->ControllerId) {
            $array = parent::getReactionButtonProperties($theah);
            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $mover->ControllerId);
            foreach ($hand as $card) {
                $array[] = $this->createButtonProperty($game, $card->Name, 'discardHand-' . $card->Id);
            }

            return $array;
        }

        if (! $this->awaitingMoverDiscard && $activeId === $rosa->ControllerId) {
            $array = parent::getReactionButtonProperties($theah);
            $handCount = 0;
            if ($mover) {
                $handCount = count($theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $mover->ControllerId));
            }
            if ($handCount > 0) {
                $array[] = $this->createButtonProperty($game, $game->translate('Have Them Discard'), 'haveThemDiscard');
            }
            $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');

            return $array;
        }

        return parent::getReactionButtonProperties($theah);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $rosa = $this->getOwningCharacter($game->theah);
        $mover = $this->pendingMoverCharacterId ? $game->theah->getCharacterById($this->pendingMoverCharacterId) : null;

        if ($reactionId === 'pass' && ! $this->awaitingMoverDiscard) {
            $this->pendingMoverCharacterId = 0;
            if ($rosa) {
                $rosa->IsUpdated = true;
            }
            $game->gamestate->nextState('done');

            return;
        }

        if ($reactionId === 'haveThemDiscard' && ! $this->awaitingMoverDiscard && $rosa) {
            if (! $mover) {
                $this->pendingMoverCharacterId = 0;
                $rosa->IsUpdated = true;
                $game->gamestate->nextState('done');

                return;
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $mover->ControllerId);
            if (count($hand) === 0) {
                $game->notify->all('message', clienttranslate('${card_inject_code}: No cards in hand to discard.'), [
                    'card_inject_code' => $mover->getInjectCode(),
                ]);
                $this->pendingMoverCharacterId = 0;
                $rosa->IsUpdated = true;
                $game->gamestate->nextState('done');

                return;
            }

            $this->awaitingMoverDiscard = true;
            $rosa->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($mover->ControllerId, $rosa->Id, $this->Id);
            $game->theah->queueEvent($transition);
            $game->gamestate->nextState('done');

            return;
        }

        if (str_starts_with($reactionId, 'discardHand-') && $this->awaitingMoverDiscard && $mover && $rosa) {
            $cardId = (int) substr($reactionId, strlen('discardHand-'));
            if ((int) $game->getActivePlayerId() !== $mover->ControllerId) {
                $game->gamestate->nextState('done');
                return;
            }

            $handIds = array_map(fn ($c) => $c->Id, $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $mover->ControllerId));
            if (! in_array($cardId, $handIds, true)) {
                $game->gamestate->nextState('done');
                return;
            }

            $discarded = $game->getCardObjectFromDb($cardId);
            $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} discards ${card_inject_code}.'), [
                'reaction_inject_code' => $rosa->getInjectCode(),
                'player_name' => $game->getPlayerNameById($mover->ControllerId),
                'card_inject_code' => $discarded->getInjectCode(),
            ]);

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $mover->ControllerId,
                $cardId,
                $rosa->Id,
                false,
                false,
                true
            );
            $game->theah->queueEvent($discardEvent);

            $this->awaitingMoverDiscard = false;
            $this->pendingMoverCharacterId = 0;
            $this->setUsed($game->theah, true);
            $rosa->IsUpdated = true;
            $game->gamestate->nextState('done');

            return;
        }

        $game->gamestate->nextState('done');
    }
}
