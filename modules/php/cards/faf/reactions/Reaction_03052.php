<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03052 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Look at City Deck; Sink One; Reorder Rest");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may look at the top three cards of the City Deck: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Look'), 'look');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Unlabelled "At the beginning of Dusk, you may…" — Continuous Reaction
        // (Pattern D). Dusk begin fires once per day, so Continuous vs daily setUsed is
        // equivalent in practice; Continuous matches the unlabelled you-may phrasing.
        if (! ($event instanceof EventDuskPhaseBegin))
        {
            return;
        }

        if (! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        if ($owner->ControllerId == 0)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        // Precondition: at least one City Deck card to look at / sink.
        $deckCards = $event->theah->game->getCardsOnTopOfCityDeck(1);
        if (count($deckCards) == 0)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId == 'look')
        {
            $deckCards = $game->getCardsOnTopOfCityDeck(3);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at the top cards of the City Deck.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            // WHY: Multi-step sink+reorder needs chooseList states (Reaction_01144 shape).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03052", $this->Id);
            $game->theah->queueEvent($transition);

            // Continuous Reaction: intentionally do NOT call $this->setUsed(true).
            // The reaction remains available and can fire at the next Dusk beginning.
        }

        $game->gamestate->nextState("done");
    }

    public function getArgsFromReaction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromReaction($game, $state, $stateName);

        if ($state == States::DUSK_PHASE_BEGIN_03052 || $state == States::DUSK_PHASE_BEGIN_03052_2)
        {
            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromReactionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromReactionWithId($game, $state, $stateName, $id);

        if ($state == States::DUSK_PHASE_BEGIN_03052)
        {
            $owner = $this->getOwningCard($game->theah);
            $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $originalIds = array_map(fn($card) => (int) $card->id, $originalCards);

            if (! in_array($id, $originalIds, true))
            {
                throw new UserException($game->translate("Card is not among the looked-at City Deck cards."));
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null || $card->Location != Game::LOCATION_CITY_DECK)
            {
                throw new UserException($game->translate("Card is not in the City Deck."));
            }

            // WHY: Text says "sink", not discard — bottom of City Deck (Kaspar 01035), not city discard (02014).
            $sinkEvent = EventFactory::createCardAddedToCityDeckEvent($owner->ControllerId, $id, false);
            $game->theah->queueEvent($sinkEvent);

            $remainingCards = array_values(array_filter($originalCards, fn($c) => (int) $c->id != $id));
            $game->globals->set(Game::CHOSEN_CARD, json_encode($remainingCards));

            if (count($remainingCards) == 0)
            {
                $game->gamestate->nextState("done");
                return;
            }

            if (count($remainingCards) == 1)
            {
                // Order is forced — put the remaining card back on top and finish.
                $deck = $game->getGameDeckObject();
                $deck->insertCardOnExtremePosition((int) $remainingCards[0]->id, Game::LOCATION_CITY_DECK, true);

                $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has replaced the remaining City Deck card.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);

                $game->gamestate->nextState("done");
                return;
            }

            $game->gamestate->nextState("reorder");
        }
    }

    public function actFromReactionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromReactionWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUSK_PHASE_BEGIN_03052_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject();

            $remainingCards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $remainingIds = array_map(fn($c) => (int) $c->id, $remainingCards);

            if (count($ids) != count($remainingIds))
            {
                throw new UserException($game->translate("You must order all remaining cards."));
            }

            foreach ($ids as $id)
            {
                if (! in_array((int) $id, $remainingIds, true))
                {
                    throw new UserException(sprintf($game->translate("Card %s is not in the remaining cards."), $id));
                }

                // Move card to top of City Deck (Penya / 02014 order: last selected ends on top after descending sort in JS).
                $deck->insertCardOnExtremePosition((int) $id, Game::LOCATION_CITY_DECK, true);
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in the City Deck.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $game->gamestate->nextState();
        }
    }
}
