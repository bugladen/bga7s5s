<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01090 extends Technique
{
    private int $RevealedCardId = 0;
    private int $CardPlayerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Reveal and Replace the Top Card of Adversary's Faction Deck");
        $this->RevealedCardId = 0;
        $this->CardPlayerId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        return $inDuel;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);

            $actor = $game->theah->getDuelRoundActor();
            $playerName = $game->getPlayerNameById($actor->ControllerId);

            $adversary = $game->theah->getDuelRoundOpponent();
            $opponentName = $game->getPlayerNameById($adversary->ControllerId);

            $dbCardInfo = $game->getCardsOnTopOfPlayerFactionDeck($adversary->ControllerId, 1)[0];
            $card = $game->getCardObjectFromDb($dbCardInfo['id']);

            $this->RevealedCardId = $card->Id;
            $this->CardPlayerId = $adversary->ControllerId;
            $owner->IsUpdated = true;

            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} reveals and replaces the top card of ${opponent_name}\'s Faction Deck. Card revealed: ${card_inject_code}.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $playerName,
                "opponent_name" => $opponentName,
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "01090", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->RevealedCardId = 0;
            $this->CardPlayerId = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->RevealedCardId = 0;
            $this->CardPlayerId = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelNewRound && $this->CardPlayerId > 0)
        {
            $adversary = $event->theah->getDuelRoundActor();
            if ($this->CardPlayerId == $adversary->ControllerId)
            {
                $owner = $this->getOwningCharacter($event->theah);
                $transition = EventFactory::createTechniqueTransitionEvent($adversary->ControllerId, $owner->Id, "01090", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01090 || $state == States::DUEL_NEW_ROUND_01090)
        {
            $adversary = $game->theah->getDuelRoundOpponent();
            $playerName = $game->getPlayerNameById($adversary->ControllerId);
            $args['opponentName'] = $playerName;

            $card = $game->getCardObjectFromDb($this->RevealedCardId);
            $args['card'] = $card->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_NEW_ROUND_01090)
        {
            //Take a wound
            if ($id == 0)
            {
                $owner = $this->getOwningCharacter($game->theah);
                $actor = $game->theah->getDuelRoundActor();
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($actor->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }
            else
            {
                $actor = $game->theah->getDuelRoundActor();
                $deck = $game->getGameDeckObject();
                $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $actor->ControllerId);
                $hand = array_filter($hand, fn($card) => $card['id'] == $id);
                if (count($hand) == 0)
                {
                    throw new \BgaUserException($game->translate("Card is not in your hand."));
                }

                $owner = $this->getOwningCard($game->theah);
                $card = $game->getCardObjectFromDb($id);
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->ControllerId, $card->Id, $owner->Id, false, false, true);
                $game->theah->queueEvent($discardEvent);

                $card = $game->getCardObjectFromDb($this->RevealedCardId);

                // Move from faction deck into hand, then on to the dueling line
                // (mirrors the normal combat-card flow in actChooseCombatCard).
                // Done with direct DB ops so we can chain both moves synchronously
                // — using EventCardAddedToHand here would fire after the dueling-line
                // move and put the card back into the hand.
                $game->moveCard($card->Id, Game::LOCATION_HAND, $actor->ControllerId, $card);
                $game->theah->addCardToWorld($card);

                $game->notify->all("cardAddedToHand", clienttranslate('${player_name} added ${card_inject_code} to their Faction Hand.'), [
                    "player_id" => $actor->ControllerId,
                    "player_name" => $game->getPlayerNameById($actor->ControllerId),
                    "card_inject_code" => $card->getInjectCode(),
                    "card" => $card->getPropertyArray($game),
                    "handCount" => count($deck->getPlayerHand($actor->ControllerId)),
                ]);

                $game->globals->set(Game::CHOSEN_CARD, $card->Id);

                $event = EventFactory::createCombatCardAnnouncedEvent($actor->ControllerId, $owner->Id);
                $game->theah->queueEvent($event);

                $game->moveCard($card->Id, Game::LOCATION_DUELING_LINE, $actor->ControllerId, $card);

                $transition = EventFactory::createTransitionEvent($actor->ControllerId, $owner->Id, "01090_2", $this->Id);
                $game->theah->queueEvent($transition);
            }

            $game->gamestate->nextState();
        }
    }
}