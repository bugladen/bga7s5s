<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IFactionCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03059 extends Maneuver
{
    private bool $ChooseParry = false;
    private int $BonusAmount = 0;
    private int $RevealedCardId = 0;
    private int $AdversaryPlayerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Look at Adversary Deck; Reveal One for Parry or Thrust");
        $this->ChooseParry = false;
        $this->BonusAmount = 0;
        $this->RevealedCardId = 0;
        $this->AdversaryPlayerId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        // WHY: getCardsOnTopOfPlayerFactionDeck reshuffles discard when short; still need ≥1 card total.
        $deck = $theah->game->getGameDeckObject();
        $deckName = $theah->game->getPlayerFactionDeckName($adversary->ControllerId);
        $discardName = $theah->game->getPlayerDiscardDeckName($adversary->ControllerId);
        $total = $deck->countCardsInLocation($deckName) + $deck->countCardsInLocation($discardName);

        return $total > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary === null)
            {
                return;
            }

            $this->AdversaryPlayerId = $adversary->ControllerId;
            $owner->IsUpdated = true;

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($adversary->ControllerId, 3);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }
            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at the top cards of ${opponent_name}\'s Faction Deck.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "opponent_name" => $game->getPlayerNameById($adversary->ControllerId),
            ]);

            // WHY: stackEvent so look/reveal/stat prompts fire before calc (Pattern C.3).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03059", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            if ($this->BonusAmount <= 0)
            {
                return;
            }

            $owner = $this->getOwningCard($event->theah);
            if ($this->ChooseParry)
            {
                $event->parry += $this->BonusAmount;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s adds %d Parry."),
                    $owner->getInjectCode(),
                    $this->BonusAmount
                );
            }
            else
            {
                $event->thrust += $this->BonusAmount;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s adds %d Thrust."),
                    $owner->getInjectCode(),
                    $this->BonusAmount
                );
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->ChooseParry = false;
            $this->BonusAmount = 0;
            $this->RevealedCardId = 0;
            $this->AdversaryPlayerId = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
            $event->theah->game->globals->delete(Game::CHOSEN_CARD);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059)
        {
            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
            $args['cards'] = $cards;
            $args['opponentName'] = $game->getPlayerNameById($this->AdversaryPlayerId);
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059_3
            || $state == States::DUEL_RESOLVE_MANEUVER_03059_4)
        {
            // WHY: Sink/reorder are only the unchosen looked-at cards — the revealed pick stays where it is.
            $args['cards'] = $this->getUnchosenLookedAtCards($game);
            $args['opponentName'] = $game->getPlayerNameById($this->AdversaryPlayerId);
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059_2)
        {
            $revealed = $game->getCardObjectFromDb($this->RevealedCardId);
            $args['parry'] = $this->getPrintedParry($revealed);
            $args['thrust'] = $this->getPrintedThrust($revealed);
            $args['revealedCardInjectCode'] = $revealed !== null ? $revealed->getInjectCode() : '';
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059)
        {
            $owner = $this->getOwningCard($game->theah);
            $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
            $originalIds = array_map(fn($card) => (int) $card->id, $originalCards);

            if (! in_array($id, $originalIds, true))
            {
                throw new UserException($game->translate("Card is not among the looked-at Faction Deck cards."));
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null)
            {
                throw new UserException($game->translate("Card not found."));
            }

            $deckName = $game->getPlayerFactionDeckName($this->AdversaryPlayerId);
            if ($card->Location != $deckName)
            {
                throw new UserException($game->translate("Card is not in the Adversary's Faction Deck."));
            }

            $this->RevealedCardId = $id;
            $owner->IsUpdated = true;

            // WHY: No createCardRevealedEvent for faction peeks — public notify is the reveal (Action_01038).
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} reveals ${revealed_inject_code}.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "revealed_inject_code" => $card->getInjectCode(),
            ]);

            // WHY: stackEvent (not queueEvent) — Resolve + Calculate still pending (Pattern C.3 / Maneuver_03035).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03059_2", $this->Id);
            $game->theah->stackEvent($transition);
            $game->gamestate->nextState();
            return;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $revealed = $game->getCardObjectFromDb($this->RevealedCardId);
            if ($revealed === null)
            {
                throw new UserException($game->translate("Revealed card not found."));
            }

            if ($id == 1)
            {
                $this->ChooseParry = true;
                $this->BonusAmount = $this->getPrintedParry($revealed);
                $game->notify->all("message", clienttranslate('${card_inject_code} adds ${amount} Parry from ${revealed_inject_code}.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "amount" => $this->BonusAmount,
                    "revealed_inject_code" => $revealed->getInjectCode(),
                ]);
            }
            else if ($id == 2)
            {
                $this->ChooseParry = false;
                $this->BonusAmount = $this->getPrintedThrust($revealed);
                $game->notify->all("message", clienttranslate('${card_inject_code} adds ${amount} Thrust from ${revealed_inject_code}.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "amount" => $this->BonusAmount,
                    "revealed_inject_code" => $revealed->getInjectCode(),
                ]);
            }
            else
            {
                throw new UserException($game->translate("Invalid choice"));
            }

            $owner->IsUpdated = true;
            $this->continueAfterStatChoice($game);
            return;
        }

        $game->gamestate->nextState();
    }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromManeuverWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059_3)
        {
            $owner = $this->getOwningCard($game->theah);
            $actor = $game->theah->getDuelRoundActor();
            if ($actor === null || ! $actor->hasTrait('Academic'))
            {
                throw new UserException($game->translate("Only an Academic participant may sink cards."));
            }

            $unchosenCards = $this->getUnchosenLookedAtCards($game);
            $unchosenIds = array_map(fn($card) => (int) $card->id, $unchosenCards);
            $deckName = $game->getPlayerFactionDeckName($this->AdversaryPlayerId);

            foreach ($ids as $id)
            {
                $id = (int) $id;
                if (! in_array($id, $unchosenIds, true))
                {
                    throw new UserException($game->translate("Card is not among the unchosen looked-at Faction Deck cards."));
                }

                $card = $game->getCardObjectFromDb($id);
                if ($card === null || $card->Location != $deckName)
                {
                    throw new UserException($game->translate("Card is not in the Adversary's Faction Deck."));
                }
            }

            $deck = $game->getGameDeckObject();
            foreach ($ids as $id)
            {
                $id = (int) $id;
                $card = $game->getCardObjectFromDb($id);
                // WHY: Immediate bottom insert (Technique_01010) — queued sink events would race
                // with finishReplaceOrReorder's top inserts before EVENTS drains.
                $deck->insertCardOnExtremePosition($id, $deckName, false);

                $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} sinks ${sunk_inject_code}.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "sunk_inject_code" => $card->getInjectCode(),
                ]);

                $unchosenCards = array_values(array_filter($unchosenCards, fn($c) => (int) $c->id != $id));
            }

            $game->globals->set(Game::CHOSEN_CARD, json_encode($unchosenCards));
            $this->finishReplaceOrReorder($game);
            return;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059_4)
        {
            $owner = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject();
            $deckName = $game->getPlayerFactionDeckName($this->AdversaryPlayerId);

            $remainingCards = $this->getUnchosenLookedAtCards($game);
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

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in ${opponent_name}\'s Faction Deck.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "opponent_name" => $game->getPlayerNameById($this->AdversaryPlayerId),
            ]);

            $game->gamestate->nextState();
            return;
        }

        $game->gamestate->nextState();
    }

    public function actFromManeuverPass(Game $game, int $state): void
    {
        parent::actFromManeuverPass($game, $state);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03059_3)
        {
            // WHY: "sink any" includes zero — Pass = sink none, then replace/reorder.
            $this->finishReplaceOrReorder($game);
        }
    }

    private function continueAfterStatChoice(Game $game): void
    {
        $owner = $this->getOwningCard($game->theah);
        $actor = $game->theah->getDuelRoundActor();
        $unchosen = $this->getUnchosenLookedAtCards($game);

        if ($actor !== null && $actor->hasTrait('Academic') && count($unchosen) > 0)
        {
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03059_3", $this->Id);
            $game->theah->stackEvent($transition);
            $game->gamestate->nextState();
            return;
        }

        $this->finishReplaceOrReorder($game);
    }

    private function finishReplaceOrReorder(Game $game): void
    {
        $owner = $this->getOwningCard($game->theah);
        // WHY: Only unchosen looked-at cards are replaced/reordered — the revealed pick is excluded.
        $remaining = $this->getUnchosenLookedAtCards($game);
        $count = count($remaining);
        $deck = $game->getGameDeckObject();
        $deckName = $game->getPlayerFactionDeckName($this->AdversaryPlayerId);

        if ($count == 0)
        {
            $game->gamestate->nextState();
            return;
        }

        if ($count == 1)
        {
            $deck->insertCardOnExtremePosition((int) $remaining[0]->id, $deckName, true);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has replaced the remaining Faction Deck card.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $game->gamestate->nextState();
            return;
        }

        $game->globals->set(Game::CHOSEN_CARD, json_encode($remaining));

        $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03059_4", $this->Id);
        $game->theah->stackEvent($transition);
        $game->gamestate->nextState();
    }

    /**
     * Looked-at cards excluding the one revealed for Parry/Thrust.
     *
     * @return list<object>
     */
    private function getUnchosenLookedAtCards(Game $game): array
    {
        $cards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
        if (! is_array($cards))
        {
            return [];
        }

        return array_values(array_filter(
            $cards,
            fn($card) => (int) $card->id != $this->RevealedCardId
        ));
    }

    private function getPrintedParry(?Card $card): int
    {
        if ($card instanceof IFactionCard)
        {
            return $card->Parry;
        }

        return 0;
    }

    private function getPrintedThrust(?Card $card): int
    {
        if ($card instanceof IFactionCard)
        {
            return $card->Thrust;
        }

        return 0;
    }
}
