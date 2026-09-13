<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03006 extends CardReaction
{
    // WHY public: multi-stage cross-player state must survive serialize/DB round-trips
    // (same discipline as Reaction_03044 / Reaction_03068). Private nested fields can
    // reset on reload — then playerId=0 skips changeActivePlayer and Premonition's
    // owner stays active seeing the opponent's hand as sink buttons.
    // '' (idle), 'offer' (owner clicks Force Sink/Pass),
    // 'pick1' (opponent picks first card), 'pick2' (opponent picks second card)
    public string $stage = '';
    public int $opponentId = 0;
    public int $performerId = 0;
    public int $targetCharacterId = 0;
    public int $cardsSunk = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Opponent Sinks Two Cards from Their Hand");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        switch ($this->stage)
        {
            case 'offer':
                $target = $this->targetCharacterId > 0 ? $theah->getCharacterById($this->targetCharacterId) : null;
                $targetName = $target ? $target->Name : $theah->game->translate('your character');
                return $base . sprintf($theah->game->translate('${you} may force the opposing player to sink two cards from their hand after they targeted %s: '), $targetName);
            case 'pick1':
            case 'pick2':
                return $base . $theah->game->translate('${you} must choose a card from your hand to sink: ');
        }
        return $base;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        switch ($this->stage)
        {
            case 'offer':
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Force Sink'), 'sink');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
                break;

            case 'pick1':
            case 'pick2':
                $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
                foreach ($hand as $card)
                {
                    $array[] = $this->createButtonProperty($theah->game, $card->Name, "card-{$card->Id}");
                }
                break;
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCard($event?->theah);
        if ($owner == null)
        {
            return;
        }

        if ($event instanceof EventSorcererAbilityPlayed || $event instanceof EventRangedAbilityPlayed)
        {
            if ($event->targetId == 0)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->targetId, (int)$event->playerId);
            return;
        }

        if ($event instanceof EventCharacterTargeted)
        {
            if ($event->canceled || $event->targetId == 0)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->targetId, (int)$event->playerId);
            return;
        }

        if ($event instanceof EventCardEngaged || $event instanceof EventCardEngarded)
        {
            if ($event->canceled)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->cardId, (int)$event->playerId);
            return;
        }

        if ($event instanceof EventCardMoving)
        {
            if ($event->canceled)
            {
                return;
            }
            // EventCardMoving has no playerId; opponent comes from source card.
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->cardId);
            return;
        }

        if ($event instanceof EventCharacterBeingWounded || $event instanceof EventCharacterBeingHealed)
        {
            if ($event->canceled)
            {
                return;
            }
            // Wound/heal events have no playerId; opponent comes from source card.
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->characterId);
            return;
        }

        if ($event instanceof EventChallengeIssued)
        {
            if ($event->canceled)
            {
                return;
            }
            // WHY: Challenges always choose a defender. Skip IAbilityThatTargetsCharacters —
            // BasicChallenge has sourceId=0, and activating a technique before issue overwrites
            // TRANSITION_INTERNAL_ID so abilityId is no longer 'BasicChallenge'. Same playerId
            // fallback as Reaction_01014 for sourceless basic challenges.
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->defenderId, (int)$event->playerId, false);
            return;
        }
    }

    private function maybeTrigger(Event $event, int $sourceId, string $abilityId, int $targetCharacterId, int $initiatingPlayerId = 0, bool $requireCharacterTargetingAbility = true): void
    {
        $theah = $event->theah;
        $owner = $this->getOwningCard($theah);
        if ($owner == null)
        {
            return;
        }

        if ($requireCharacterTargetingAbility && ! $this->sourceAbilityTargetsCharacters($theah, $sourceId, $abilityId))
        {
            return;
        }

        $target = $theah->getCharacterById($targetCharacterId);
        if ($target == null)
        {
            return;
        }

        if ($target->ControllerId != $owner->ControllerId)
        {
            return;
        }

        // WHY: BasicChallengeAction fires with sourceId=0 (no source card). Opponent is the
        // initiating player — same fallback Reaction_01014 uses via $event->playerId.
        $source = $theah->getCardById($sourceId);
        $opposingPlayerId = $source ? $source->ControllerId : $initiatingPlayerId;
        if ($opposingPlayerId == 0 || $opposingPlayerId == $owner->ControllerId)
        {
            return;
        }

        $performer = $this->findStregaPerformerAtLocation($theah, $owner->ControllerId, $target->Location);
        if ($performer == null)
        {
            return;
        }

        if (! $this->opponentHasMoreCardsInHand($theah, $opposingPlayerId, $owner->ControllerId))
        {
            return;
        }

        $this->stage = 'offer';
        $this->opponentId = $opposingPlayerId;
        $this->performerId = $performer->Id;
        $this->targetCharacterId = $target->Id;
        $this->cardsSunk = 0;
        $this->persistReactionState($theah->game, $owner);

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function sourceAbilityTargetsCharacters(Theah $theah, int $sourceId, string $abilityId): bool
    {
        if ($abilityId == '')
        {
            return false;
        }

        $source = $theah->getCardById($sourceId);
        if ($source != null)
        {
            $ability = $source->getAbilityById($abilityId);
            if ($ability instanceof IAbilityThatTargetsCharacters)
            {
                return true;
            }
        }

        $action = $theah->getInPlayActionById($abilityId);
        return $action instanceof IAbilityThatTargetsCharacters;
    }

    private function findStregaPerformerAtLocation(Theah $theah, int $controllerId, string $location): ?Character
    {
        $characters = $theah->getCharactersAtLocation($location);
        foreach ($characters as $character)
        {
            if ($character->ControllerId == $controllerId && $character->hasTrait("Strega"))
            {
                return $character;
            }
        }
        return null;
    }

    private function opponentHasMoreCardsInHand(Theah $theah, int $opponentId, int $ownerControllerId): bool
    {
        $opponentHand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $opponentId);
        $ownerHand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $ownerControllerId);
        return count($opponentHand) > count($ownerHand);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $owner = $this->getOwningCard($game->theah);
        if ($owner == null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        // WHY: Keep the mutating reaction and theah->cards graph on one instance before
        // any stage write. getCardById can return a DB copy that is not in theah->cards;
        // writing that copy (or a second fetch) leaves stage stuck on 'offer' for the
        // next playerReaction args load.
        $game->theah->addCardToWorld($owner);

        $activeId = (int)$game->getActivePlayerId();
        $ownerId = (int)$owner->ControllerId;

        if ($this->stage === 'offer')
        {
            if ($reactionId === 'sink')
            {
                // WHY: Do NOT call parent::performReaction here. It stacks EventReactionActivated
                // at HIGHEST_PRIORITY; other reactions can steal the next playerReaction
                // transition before our opponent-pick handoff runs, so the opponent reloads
                // still on stage 'offer' (Force Sink/Pass) and a second 'sink' then confuses
                // who is the sinker — Premonition's owner ends up picking from their own hand.
                if ($activeId === $ownerId)
                {
                    $this->announceForcedOpponent($game, $owner);
                    if (! $this->advanceToNextPick($game, $owner))
                    {
                        $this->finalize($game, $owner);
                    }
                    $game->gamestate->nextState("done");
                    return;
                }

                // Desync repair: opponent was wrongly left on the offer UI.
                if ($activeId === $this->opponentId)
                {
                    if (! $this->advanceToNextPick($game, $owner))
                    {
                        $this->finalize($game, $owner);
                    }
                    $game->gamestate->nextState("done");
                    return;
                }

                throw new \Bga\GameFramework\UserException($game->translate("Only Premonition's controller may force the sink."));
            }

            // Pass — only the scheme owner.
            if ($activeId !== $ownerId)
            {
                throw new \Bga\GameFramework\UserException($game->translate("Only Premonition's controller may pass this reaction."));
            }

            parent::performReaction($game, $state, $internalId, $reactionId);
            $this->resetStage();
            $this->persistReactionState($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'pick1' || $this->stage === 'pick2')
        {
            // WHY: Card picks belong only to the triggering opponent — never Premonition's owner.
            // Skip parent::performReaction — same ReactionActivated race as the offer→pick handoff.
            if ($activeId !== $this->opponentId)
            {
                throw new \Bga\GameFramework\UserException($game->translate("Only the opposing player may choose cards to sink."));
            }

            if (str_starts_with($reactionId, 'card-'))
            {
                $cardId = (int)substr($reactionId, strlen('card-'));
                $this->sinkOneFromHand($game, $owner, $cardId);
                $this->cardsSunk++;

                if ($this->cardsSunk < 2 && $this->advanceToNextPick($game, $owner))
                {
                    $game->gamestate->nextState("done");
                    return;
                }

                $this->finalize($game, $owner);
                $game->gamestate->nextState("done");
                return;
            }
        }

        $game->gamestate->nextState("done");
    }

    private function announceForcedOpponent(Game $game, Card $owner): void
    {
        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} forces ${opponent_name} to sink two cards from their hand.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
            "opponent_name" => $game->getPlayerNameById($this->opponentId),
        ]);
    }

    private function advanceToNextPick(Game $game, Card $owner): bool
    {
        if ($this->opponentId == 0 || $this->opponentId == $owner->ControllerId)
        {
            return false;
        }

        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
        if (count($hand) == 0)
        {
            return false;
        }

        $this->stage = ($this->cardsSunk == 0) ? 'pick1' : 'pick2';
        $this->persistReactionState($game, $owner);

        $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
        // WHY: Ahead of other REACTION_PRIORITY offers queued while we skip ReactionActivated.
        $transition->priority = Event::HIGH_PRIORITY;
        $game->theah->queueEvent($transition);

        return true;
    }

    private function persistReactionState(Game $game, Card $owner): void
    {
        $game->theah->addCardToWorld($owner);

        // WHY: If $this is a detached reaction instance, copy stage onto the reaction
        // actually nested on $owner before serialize — otherwise DB keeps stage 'offer'.
        $hosted = $owner->getReactionById($this->Id);
        if ($hosted !== null && $hosted !== $this)
        {
            $hosted->stage = $this->stage;
            $hosted->opponentId = $this->opponentId;
            $hosted->performerId = $this->performerId;
            $hosted->targetCharacterId = $this->targetCharacterId;
            $hosted->cardsSunk = $this->cardsSunk;
            $hosted->Used = $this->Used;
        }

        $owner->IsUpdated = true;
        $game->updateCardObjectInDb($owner);
    }

    private function sinkOneFromHand(Game $game, Card $owner, int $cardId): void
    {
        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
        $handIds = array_map(fn($c) => $c->Id, $hand);

        if (! in_array($cardId, $handIds))
        {
            throw new \Bga\GameFramework\UserException($game->translate("Selected card is not in your hand."));
        }

        $card = $game->getCardObjectFromDb($cardId);
        $deck = $game->getGameDeckObject();
        $deckName = $game->getPlayerFactionDeckName($this->opponentId);

        // WHY: Must update Card->Location too — insertCard alone leaves Location=Hand
        // while card_location becomes Faction-*. Gamble confirm (and similar) then
        // reject a card the UI offered from the deck tops.
        $deck->insertCardOnExtremePosition($cardId, $deckName, false);
        $card->Location = $deckName;
        $game->updateCardObjectInDb($card);

        $game->notify->player($this->opponentId, "cardRemovedFromHand", clienttranslate('Private: ${reaction_inject_code}: you sink ${card_inject_code} from your hand.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "card_inject_code" => $card->getInjectCode(),
            "playerId" => $this->opponentId,
            "cardId" => $cardId,
            "handCount" => count($deck->getPlayerHand($this->opponentId)),
        ]);

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} sinks a card from their hand.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($this->opponentId),
        ]);
    }

    private function finalize(Game $game, Card $owner): void
    {
        $this->setUsed($game->theah, true);
        $this->resetStage();
        $this->persistReactionState($game, $owner);
    }

    private function resetStage(): void
    {
        $this->stage = '';
        $this->opponentId = 0;
        $this->performerId = 0;
        $this->targetCharacterId = 0;
        $this->cardsSunk = 0;
    }
}
