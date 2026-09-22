<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04cd15 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage: Look at Top of Deck; Sink Any; Reorder; May Discard to Draw");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $attachment = $this->getOwningAttachment($theah);
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        // WHY: printed City Action — gate with cardInCity (no AttachmentCityAction base).
        if ($owner === null || ! $theah->cardInCity($owner))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $attachment = $this->getOwningAttachment($event->theah);
            $game = $event->theah->game;
            $controllerId = $attachment->ControllerId;

            // WHY: printed cost is engage this attachment; pay on trigger (action already confirmed centrally).
            $engageEvent = EventFactory::createCardEngagedEvent(
                $controllerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $event->theah->queueEvent($engageEvent);

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($controllerId, 3);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }
            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at the top cards of their Faction Deck.'), [
                "card_inject_code" => $attachment->getInjectCode(),
                "player_name" => $game->getPlayerNameById($controllerId),
            ]);

            // WHY: Empty look skips sink/reorder — still offer optional discard-to-draw.
            $transitionName = count($cards) > 0 ? "04cd15" : "04cd15_3";
            $transition = EventFactory::createTransitionEvent(
                $controllerId,
                $attachment->Id,
                $transitionName,
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $owner = $this->getOwningCharacter($game->theah);
        if ($owner !== null)
        {
            $args["performerId"] = $owner->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD15
            || $state == States::HIGH_DRAMA_PLAYER_TURN_04CD15_2)
        {
            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
            $args['cards'] = is_array($cards) ? $cards : [];
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD15)
        {
            $this->handleSinkChosen($game, $ids);
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD15_2)
        {
            $this->handleReorderChosen($game, $ids);
            return;
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD15_3)
        {
            $this->handleDiscardChosen($game, $id);
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD15)
        {
            // WHY: "sink any" includes zero — Pass = sink none, then replace/reorder (03059).
            $this->finishReplaceOrReorder($game);
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD15_3)
        {
            $attachment = $this->getOwningAttachment($game->theah);
            $controllerId = $attachment->ControllerId;

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} declines to discard a card to draw.'), [
                "card_inject_code" => $attachment->getInjectCode(),
                "player_name" => $game->getPlayerNameById($controllerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($controllerId);
            $game->theah->queueEvent($actionResolvedEvent);
            $game->gamestate->nextState("pass");
        }
    }

    /**
     * @param list<int|string> $ids
     */
    private function handleSinkChosen(Game $game, array $ids): void
    {
        $attachment = $this->getOwningAttachment($game->theah);
        $controllerId = $attachment->ControllerId;
        $deckName = $game->getPlayerFactionDeckName($controllerId);
        $deck = $game->getGameDeckObject();

        $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
        if (! is_array($originalCards))
        {
            $originalCards = [];
        }
        $originalIds = array_map(fn($card) => (int) $card->id, $originalCards);

        foreach ($ids as $id)
        {
            $id = (int) $id;
            if (! in_array($id, $originalIds, true))
            {
                throw new UserException($game->translate("Card is not among the looked-at Faction Deck cards."));
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null || $card->Location != $deckName)
            {
                throw new UserException($game->translate("Card is not in your Faction Deck."));
            }
        }

        foreach ($ids as $id)
        {
            $id = (int) $id;
            $card = $game->getCardObjectFromDb($id);
            // WHY: Immediate bottom insert (Maneuver_03059 / Technique_01010) — queued sink
            // events would race finishReplaceOrReorder's top inserts before EVENTS drains.
            $deck->insertCardOnExtremePosition($id, $deckName, false);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} sinks ${sunk_inject_code}.'), [
                "card_inject_code" => $attachment->getInjectCode(),
                "player_name" => $game->getPlayerNameById($controllerId),
                "sunk_inject_code" => $card->getInjectCode(),
            ]);

            $originalCards = array_values(array_filter($originalCards, fn($c) => (int) $c->id != $id));
        }

        $game->globals->set(Game::CHOSEN_CARD, json_encode($originalCards));
        $this->finishReplaceOrReorder($game);
    }

    /**
     * @param list<int|string> $ids
     */
    private function handleReorderChosen(Game $game, array $ids): void
    {
        $attachment = $this->getOwningAttachment($game->theah);
        $controllerId = $attachment->ControllerId;
        $deckName = $game->getPlayerFactionDeckName($controllerId);
        $deck = $game->getGameDeckObject();

        $remainingCards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
        if (! is_array($remainingCards))
        {
            $remainingCards = [];
        }
        $remainingIds = array_map(fn($c) => (int) $c->id, $remainingCards);

        if (count($ids) != count($remainingIds))
        {
            throw new UserException($game->translate("You must order all remaining cards."));
        }

        foreach ($ids as $id)
        {
            $id = (int) $id;
            if (! in_array($id, $remainingIds, true))
            {
                throw new UserException(sprintf($game->translate("Card %s is not in the remaining cards."), $id));
            }

            // Last selected ends on top after descending sort in JS (onCardsSorted).
            $deck->insertCardOnExtremePosition($id, $deckName, true);
        }

        $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in their Faction Deck.'), [
            "card_inject_code" => $attachment->getInjectCode(),
            "player_name" => $game->getPlayerNameById($controllerId),
        ]);

        $game->gamestate->nextState("cardsSorted");
    }

    private function handleDiscardChosen(Game $game, int $cardId): void
    {
        $attachment = $this->getOwningAttachment($game->theah);
        $controllerId = $attachment->ControllerId;
        $card = $game->theah->getCardById($cardId);

        if ($card === null || $card->Location != Game::LOCATION_HAND || $card->ControllerId != $controllerId)
        {
            throw new UserException($game->translate("Card must be in your hand."));
        }

        $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
            $controllerId,
            $card->Id,
            $attachment->Id,
            $asPayment = false,
            $asPlayed = false,
            $asEffect = true
        );
        $game->theah->queueEvent($discardEvent);

        $drawEvent = EventFactory::createCardDrawnEvent($controllerId, $attachment->getInjectCode());
        $game->theah->queueEvent($drawEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($controllerId);
        $game->theah->queueEvent($actionResolvedEvent);

        $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} discards a card to draw a card.'), [
            "card_inject_code" => $attachment->getInjectCode(),
            "player_name" => $game->getPlayerNameById($controllerId),
        ]);

        $game->gamestate->nextState("discardChosen");
    }

    private function finishReplaceOrReorder(Game $game): void
    {
        $attachment = $this->getOwningAttachment($game->theah);
        $controllerId = $attachment->ControllerId;
        $remaining = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
        if (! is_array($remaining))
        {
            $remaining = [];
        }
        $count = count($remaining);
        $deck = $game->getGameDeckObject();
        $deckName = $game->getPlayerFactionDeckName($controllerId);

        if ($count == 0)
        {
            $game->gamestate->nextState("discardChoice");
            return;
        }

        if ($count == 1)
        {
            $deck->insertCardOnExtremePosition((int) $remaining[0]->id, $deckName, true);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has replaced the remaining Faction Deck card.'), [
                "card_inject_code" => $attachment->getInjectCode(),
                "player_name" => $game->getPlayerNameById($controllerId),
            ]);

            $game->gamestate->nextState("discardChoice");
            return;
        }

        $game->globals->set(Game::CHOSEN_CARD, json_encode(array_values($remaining)));
        $game->gamestate->nextState("reorder");
    }
}
