<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03007 extends AttachmentReaction implements ISorcererAbility
{
    // '' (idle), 'offer' (owner clicks Engage/Pass),
    // 'choose' (opponent picks Sink or Wound),
    // 'pick1' / 'pick2' (opponent picks card from hand to sink)
    private string $stage = '';
    private int $opponentId = 0;
    private int $cardsSunk = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Opposing Character Sent to The Locker");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        switch ($this->stage)
        {
            case 'offer':
                return $base . $theah->game->translate('${you} may engage Matushka\'s Shears to force the opposing player to wound their Leader unless they sink two cards from their hand: ');
            case 'choose':
                return $base . $theah->game->translate('${you} must wound your Leader unless you sink two cards from your hand: ');
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
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Engage and Force Choice'), 'engage');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
                break;

            case 'choose':
                $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
                $leader = $theah->getLeaderByPlayerId($this->opponentId);
                if (count($hand) >= 2)
                {
                    $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Sink two cards'), 'sink');
                }
                if ($leader != null)
                {
                    $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Leader'), 'wound');
                }
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

        if (! ($event instanceof EventCharacterDestroyed))
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner == null || $owner->Engaged)
        {
            return;
        }

        $card = $event->theah->getCardById($event->characterId);
        if ($card == null)
        {
            $card = $event->theah->game->getCardObjectFromDb($event->characterId);
        }
        if (! ($card instanceof Character))
        {
            return;
        }

        if ($event->playerId == 0 || $event->playerId == $owner->ControllerId)
        {
            return;
        }

        $this->stage = 'offer';
        $this->opponentId = $event->playerId;
        $this->cardsSunk = 0;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningAttachment($game->theah);

        if ($this->stage === 'offer')
        {
            if ($reactionId === 'engage')
            {
                $performer = $this->getOwningCharacter($game->theah);
                $performerId = $performer ? $performer->Id : 0;

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);

                $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
                $game->theah->queueEvent($sorceryStartEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} engages ${owner_inject_code} to force ${opponent_name} to wound their Leader unless they sink two cards from their hand.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "opponent_name" => $game->getPlayerNameById($this->opponentId),
                ]);

                if (! $this->advanceToChoose($game, $owner))
                {
                    $this->finalize($game, $owner);
                }

                $game->gamestate->nextState("done");
                return;
            }

            $this->resetStage();
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'choose')
        {
            if ($reactionId === 'sink')
            {
                if ($this->advanceToNextPick($game, $owner))
                {
                    $game->gamestate->nextState("done");
                    return;
                }

                $this->woundLeader($game, $owner);
                $this->finalize($game, $owner);
                $game->gamestate->nextState("done");
                return;
            }

            if ($reactionId === 'wound')
            {
                $this->woundLeader($game, $owner);
                $this->finalize($game, $owner);
                $game->gamestate->nextState("done");
                return;
            }
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

    private function advanceToChoose(Game $game, Card $owner): bool
    {
        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
        $leader = $game->theah->getLeaderByPlayerId($this->opponentId);

        if (count($hand) < 2 && $leader == null)
        {
            return false;
        }

        if (count($hand) < 2)
        {
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${opponent_name} does not have enough cards in hand to sink two, so their Leader is wounded.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "opponent_name" => $game->getPlayerNameById($this->opponentId),
            ]);

            $this->woundLeader($game, $owner);
            return false;
        }

        $this->stage = 'choose';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);

        return true;
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

    private function woundLeader(Game $game, Card $owner): void
    {
        $leader = $game->theah->getLeaderByPlayerId($this->opponentId);
        if ($leader == null)
        {
            return;
        }

        $woundEvent = EventFactory::createCharacterBeingWoundedEvent($leader->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
        $game->theah->queueEvent($woundEvent);

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${leader_inject_code} is wounded.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "leader_inject_code" => $leader->getInjectCode(),
        ]);
    }

    private function finalize(Game $game, Card $owner): void
    {
        $performer = $this->getOwningCharacter($game->theah);
        $performerId = $performer ? $performer->Id : 0;

        $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
        $game->theah->queueEvent($sorceryPlayedEvent);

        $this->setUsed($game->theah, true);
        $this->resetStage();
        $owner->IsUpdated = true;
    }

    private function resetStage(): void
    {
        $this->stage = '';
        $this->opponentId = 0;
        $this->cardsSunk = 0;
    }
}
