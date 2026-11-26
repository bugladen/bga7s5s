<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_05Montoya extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "+1 Riposte per Flourish; draw then put a card on top of deck";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);
        return $actor !== null && $actor->hasTrait('Duelist', $owner);
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id != $combatCard->Id)
        {
            return $discount;
        }

        $playerId = $owner->ControllerId;
        // WHY: Lorenzo OVERRIDE_AS_NOT_FIRST_PLAYER — treat as not first for cost too (01112b).
        $forcedNotFirstPlayer = $theah->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
        $isFirstPlayer = $theah->game->globals->get(Game::FIRST_PLAYER) == $playerId && ! $forcedNotFirstPlayer;
        if (! $isFirstPlayer)
        {
            $discount += 1;
            $explanations[] = sprintf(
                "%s: -1 because you are not first player.",
                $owner->getInjectCode()
            );
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
            // WHY: Printed "for each Flourish in your dueling line" — include this card
            // (it is Flourish and already in the line). Contrast C.4 "other cards" unset.
            $count = 0;
            foreach ($cards as $card)
            {
                if ($card->hasTrait('Flourish'))
                {
                    $count++;
                }
            }
            if ($count > 0)
            {
                $event->riposte += $count;
                $event->explanations[] = sprintf(
                    "%s: adds %d Riposte (1 for each Flourish in your dueling line).",
                    $owner->getInjectCode(),
                    $count
                );
            }
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $playerId = $owner->ControllerId;

            $drawEvent = EventFactory::createCardDrawnEvent(
                $playerId,
                sprintf("%s Maneuver effect", $owner->getInjectCode())
            );
            $event->theah->queueEvent($drawEvent);

            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
            $deckName = $event->theah->game->getPlayerFactionDeckName($playerId);
            $deck = $event->theah->getCardObjectsAtLocation($deckName);
            // WHY: Skip chooser when both empty — draw cannot fill, put-back would softlock.
            // Nonempty deck → draw fills hand; nonempty hand → put-back without a draw.
            if (count($hand) == 0 && count($deck) == 0)
            {
                return;
            }

            $transition = EventFactory::createTransitionEvent(
                $playerId,
                $owner->Id,
                "05Montoya",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state != States::DUEL_RESOLVE_MANEUVER_05MONTOYA)
        {
            return;
        }

        $card = $game->getCardObjectFromDb($id);
        if ($card === null)
        {
            throw new UserException("Card not found");
        }

        $playerId = $game->getActivePlayerId();
        if ($card->ControllerId != $playerId)
        {
            throw new UserException("You do not control this card");
        }

        if ($card->Location != Game::LOCATION_HAND)
        {
            throw new UserException("Card not in your hand");
        }

        $owner = $this->getOwningCard($game->theah);
        $deckName = $game->getPlayerFactionDeckName($playerId);
        $deck = $game->getGameDeckObject();

        // WHY: RedTape/03006 hand→deck path — insertCard alone leaves Location=Hand while
        // card_location becomes Faction-*. EventCardAddedToFactionDeck also misses
        // cardRemovedFromHand (hand UI / opponent handCount). onTop=true = printed "on top".
        $game->insertCardOnPlayerFactionDeckExtreme($id, $playerId, true);
        $card->Location = $deckName;
        $game->updateCardObjectInDb($card);

        $game->notify->player($playerId, "cardRemovedFromHand", 'Private: ${card_inject_code}: ${picked_card} was put on top of your Faction Deck.', [
            "card_inject_code" => $owner->getInjectCode(),
            "picked_card" => $card->getInjectCode(),
            "playerId" => $playerId,
            "cardId" => $id,
            "handCount" => count($deck->getPlayerHand($playerId)),
        ]);

        $game->notify->all("message", '${card_inject_code}: ${player_name} puts a card on top of their Faction Deck.', [
            "card_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($playerId),
        ]);

        $game->gamestate->nextState();
    }
}
