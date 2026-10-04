<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;

class Reaction_04010 extends CardReaction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Reveal additional cards; Sorceries gain +1 Parry");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelGambleCardsRevealed)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            // WHY: This Reaction lives on the gambled deck card itself — not hand-paid.
            if (! in_array($owner->Id, $event->revealedCardIds, true))
            {
                return;
            }

            // WHY: Same dusk-miss class as Lodestone Action_03065 / deliverDuskEndOfDayToFactionDecks.
            // A prior Use left Used=true while the card stayed in Faction-* (peek/sink), and
            // faction decks never saw EventDuskEndOfDay until the deck dusk pass. Being among
            // revealedCardIds now means we're on the deck edge — heal so Cesca (or any Sorcerer)
            // can be offered Use/Pass. Same-day re-offer needs empty-deck reshuffle (rare).
            if ($this->Used)
            {
                $this->Used = false;
                $owner->IsUpdated = true;
            }

            if (! $this->isAvailable())
            {
                return;
            }

            $actor = $event->theah->getCharacterById($event->actorId);
            // WHY OwnerId fallback: deck Risks sometimes keep ControllerId=0 while OwnerId
            // is the faction owner; "your performer" is ownership, not in-play control.
            $ownerPlayerId = $owner->ControllerId ?: $owner->OwnerId;
            if ($actor === null || $actor->ControllerId != $ownerPlayerId)
            {
                return;
            }

            // Sorcerer Reaction — trigger-named performer is the gambler.
            if (! $actor->hasTrait("Sorcerer"))
            {
                return;
            }

            // WHY: Transition (TRANSITION_PRIORITY), not reaction (REACTION_PRIORITY). Ivy-style pre-choose
            // reactions run first; then this state shows the revealed cards in chooseList
            // BEFORE Use/Pass so the player can see Unravel among them.
            $transition = EventFactory::createTransitionEvent(
                $ownerPlayerId,
                $owner->Id,
                "04010",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromReaction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromReaction($game, $state, $stateName);

        if ($state == States::DUEL_GAMBLE_REVEALED_04010)
        {
            // WHY: Public cards — hub already announced names on reveal; player must see
            // the chooseList (including this card) before deciding Use/Pass.
            $actor = $game->theah->getDuelRoundActor();
            $count = $game->globals->get(Game::GAMBLE_REVEAL_COUNT, 2);
            $fromBottom = $game->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);
            $deckCards = $fromBottom
                ? $game->getCardsOnBottomOfPlayerFactionDeck($actor->ControllerId, $count)
                : $game->getCardsOnTopOfPlayerFactionDeck($actor->ControllerId, $count);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                if ($card)
                {
                    $cards[] = $card->getPropertyArray($game);
                }
            }
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromReactionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromReactionWithId($game, $state, $stateName, $id);

        if ($state != States::DUEL_GAMBLE_REVEALED_04010)
        {
            return;
        }

        // id 1 = Use
        if ($id === 1)
        {
            $this->resolveUse($game);
            // WHY: Back through REVEALED_EVENTS so additional-card reveal can offer Ivy
            // (and any other pre-choose reactions) before the final choose state.
            $game->gamestate->nextState("use");
            return;
        }

        $game->gamestate->nextState("pass");
    }

    public function actFromReactionPass(Game $game, int $state): void
    {
        parent::actFromReactionPass($game, $state);

        if ($state == States::DUEL_GAMBLE_REVEALED_04010)
        {
            $game->gamestate->nextState("pass");
        }
    }

    private function resolveUse(Game $game): void
    {
        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            return;
        }

        $actor = $game->theah->getDuelRoundActor();
        if ($actor === null || ! $actor->hasTrait("Sorcerer"))
        {
            return;
        }

        $ownerPlayerId = $owner->ControllerId ?: $owner->OwnerId;

        $sorceryStart = EventFactory::createSorcererAbilityStartEvent(
            $ownerPlayerId,
            $owner->Id,
            $this->Id,
            $actor->Id
        );
        $game->theah->queueEvent($sorceryStart);

        $additional = max(0, $actor->ModifiedInfluence);
        $currentCount = $game->globals->get(Game::GAMBLE_REVEAL_COUNT, 2);
        $fromBottom = $game->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);

        if ($additional > 0)
        {
            $newCount = $currentCount + $additional;
            $allRevealed = $fromBottom
                ? $game->getCardsOnBottomOfPlayerFactionDeck($actor->ControllerId, $newCount)
                : $game->getCardsOnTopOfPlayerFactionDeck($actor->ControllerId, $newCount);

            $additionalIds = [];
            foreach (array_slice($allRevealed, $currentCount) as $deckCard)
            {
                $cardId = (int) $deckCard['id'];
                $additionalIds[] = $cardId;
                $card = $game->getCardObjectFromDb($cardId);
                if ($card)
                {
                    $game->theah->addCardToWorld($card);
                }
            }

            $game->globals->set(Game::GAMBLE_REVEAL_COUNT, $newCount);

            if (count($additionalIds) > 0)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} reveals ${count} additional card(s) equal to ${performer_inject_code}\'s [Influence].'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($ownerPlayerId),
                    "count" => count($additionalIds),
                    "performer_inject_code" => $actor->getInjectCode(),
                ]);

                // WHY: Only the NEW ids — Unravel itself is not re-offered; Ivy can still
                // react to newly revealed Sorceries before combat-card choose.
                $revealEvent = EventFactory::createDuelGambleCardsRevealedEvent(
                    $actor->Id,
                    $actor->ControllerId,
                    $additionalIds
                );
                $game->theah->queueEvent($revealEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} would reveal ${count} additional card(s), but the deck has no more cards.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($ownerPlayerId),
                    "count" => $additional,
                ]);
            }
        }

        $game->globals->set(Game::UNRAVEL_THE_THREAD_CONTROLLER_ID, $ownerPlayerId);

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name}\'s Sorceries gain +1[Parry] this round.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($ownerPlayerId),
        ]);

        $sorceryPlayed = EventFactory::createSorcererAbilityPlayedEvent(
            $ownerPlayerId,
            $owner->Id,
            $this->Id,
            $actor->Id
        );
        $game->theah->queueEvent($sorceryPlayed);

        $this->setUsed($game->theah, true);
        $owner->IsUpdated = true;
    }
}
