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
    // '' (idle), 'offer' (owner clicks Force Sink/Pass),
    // 'pick1' (opponent picks first card), 'pick2' (opponent picks second card)
    private string $stage = '';
    private int $opponentId = 0;
    private int $performerId = 0;
    private int $targetCharacterId = 0;
    private int $cardsSunk = 0;

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
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->targetId);
            return;
        }

        if ($event instanceof EventCharacterTargeted)
        {
            if ($event->canceled || $event->targetId == 0)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->targetId);
            return;
        }

        if ($event instanceof EventCardEngaged || $event instanceof EventCardEngarded)
        {
            if ($event->canceled)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->cardId);
            return;
        }

        if ($event instanceof EventCardMoving)
        {
            if ($event->canceled)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->cardId);
            return;
        }

        if ($event instanceof EventCharacterBeingWounded || $event instanceof EventCharacterBeingHealed)
        {
            if ($event->canceled)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->characterId);
            return;
        }

        if ($event instanceof EventChallengeIssued)
        {
            if ($event->canceled)
            {
                return;
            }
            $this->maybeTrigger($event, (int)$event->sourceId, (string)$event->abilityId, (int)$event->defenderId);
            return;
        }
    }

    private function maybeTrigger(Event $event, int $sourceId, string $abilityId, int $targetCharacterId): void
    {
        $theah = $event->theah;
        $owner = $this->getOwningCard($theah);
        if ($owner == null)
        {
            return;
        }

        if (! $this->sourceAbilityTargetsCharacters($theah, $sourceId, $abilityId))
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

        $source = $theah->getCardById($sourceId);
        $opposingPlayerId = $source ? $source->ControllerId : 0;
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
        $owner->IsUpdated = true;

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
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($this->stage === 'offer')
        {
            if ($reactionId === 'sink')
            {
                $this->announceForcedOpponent($game, $owner);

                if ($this->advanceToNextPick($game, $owner))
                {
                    $game->gamestate->nextState("done");
                    return;
                }

                $this->finalize($game, $owner);
                $game->gamestate->nextState("done");
                return;
            }

            $this->resetStage();
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'pick1' || $this->stage === 'pick2')
        {
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
        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
        if (count($hand) == 0)
        {
            return false;
        }

        $this->stage = ($this->cardsSunk == 0) ? 'pick1' : 'pick2';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);

        return true;
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

        $deck->insertCardOnExtremePosition($cardId, $deckName, false);

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
        $owner->IsUpdated = true;
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
