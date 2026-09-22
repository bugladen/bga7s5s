<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04001 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Look at Top of Deck; Sink Any; Reorder Rest");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // WHY: Empty deck makes look/sink a no-op — hide rather than a useless prompt.
        $topCards = $theah->game->getCardsOnTopOfPlayerFactionDeck($owner->ControllerId, 2);
        return count($topCards) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $game = $event->theah->game;

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($owner->ControllerId, 2);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $this->setUsed($event->theah, true);

            // WHY: Deck may empty between availability and resolve — skip empty look UI.
            if (count($cards) == 0)
            {
                return;
            }

            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at the top cards of their Faction Deck.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            // WHY: createTechniqueTransitionEvent is HIGHEST_PRIORITY so the look prompt
            // runs before other queued resolve-side effects (Technique_01010 shape).
            $transition = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "04001",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }

        // EventTechniqueCanceled handler not needed
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04001
            || $state == States::DUEL_CHOOSE_TECHNIQUE_04001_2)
        {
            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
            $args['cards'] = is_array($cards) ? $cards : [];
        }

        return $args;
    }

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04001)
        {
            $this->handleSinkChosen($game, $ids);
            return;
        }

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04001_2)
        {
            $this->handleReorderChosen($game, $ids);
        }
    }

    public function actFromTechniquePass(Game $game, int $state): void
    {
        parent::actFromTechniquePass($game, $state);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04001)
        {
            // WHY: "You may sink" includes zero — Pass = sink none, then replace/reorder (04cd15).
            $this->finishReplaceOrReorder($game);
        }
    }

    /**
     * @param list<int|string> $ids
     */
    private function handleSinkChosen(Game $game, array $ids): void
    {
        $owner = $this->getOwningCharacter($game->theah);
        $controllerId = $owner->ControllerId;
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
            // WHY: Immediate bottom insert (Action_04cd15 / Technique_01010) — queued sink
            // events would race finishReplaceOrReorder's top inserts before EVENTS drains.
            $deck->insertCardOnExtremePosition($id, $deckName, false);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} sinks ${sunk_inject_code}.'), [
                "card_inject_code" => $owner->getInjectCode(),
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
        $owner = $this->getOwningCharacter($game->theah);
        $controllerId = $owner->ControllerId;
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
            "card_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($controllerId),
        ]);

        $game->gamestate->nextState("cardsSorted");
    }

    private function finishReplaceOrReorder(Game $game): void
    {
        $owner = $this->getOwningCharacter($game->theah);
        $controllerId = $owner->ControllerId;
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
            $game->gamestate->nextState("done");
            return;
        }

        if ($count == 1)
        {
            $deck->insertCardOnExtremePosition((int) $remaining[0]->id, $deckName, true);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has replaced the remaining Faction Deck card.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($controllerId),
            ]);

            $game->gamestate->nextState("done");
            return;
        }

        $game->globals->set(Game::CHOSEN_CARD, json_encode(array_values($remaining)));
        $game->gamestate->nextState("reorder");
    }
}
