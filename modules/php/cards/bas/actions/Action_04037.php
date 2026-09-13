<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04037 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard City Card; look at City Deck; extra action");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * Available = uncontrolled City Deck card that can be discarded from the city.
     *
     * @return list<ICityDeckCard>
     */
    private function getDiscardableCityCardsAtLocation(Theah $theah, string $locationName): array
    {
        $cards = [];
        foreach ($theah->getAllCards() as $card)
        {
            if (
                $card->Location === $locationName
                && ! $card->isControlled()
                && $card instanceof ICityDeckCard
                && $card->canBeDiscardedFromCity()
            )
            {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah)
            {
                // WHY En Garde Academic Action: En Garde = precondition (not Engage cost);
                // Academic = mechanical trait gate (not Sorcerer). Home is eligible in
                // principle; no City Card at Home greys those performers.
                if ($performer->Engaged || ! $performer->hasTrait("Academic"))
                {
                    return false;
                }

                return count($this->getDiscardableCityCardsAtLocation($theah, $performer->Location)) > 0;
            }
        ));
    }

    /**
     * @return list<object>
     */
    private function peekTopCityDeckCards(Game $game, int $count): array
    {
        $deckCards = $game->getCardsOnTopOfCityDeck($count);
        $cards = [];
        foreach ($deckCards as $deckCard)
        {
            $card = $game->getCardObjectFromDb($deckCard['id']);
            $cards[] = $card->getPropertyArray($game);
        }

        return $cards;
    }

    private function grantLockedExtraAction(Game $game, Character $performer): void
    {
        // WHY: optional follow-up locked to this performer (Pass allowed). EXTRA_ACTIONS
        // keeps the player; EXTRA_ACTION_PERFORMER restricts who can act if they do.
        $game->globals->set(Game::EXTRA_ACTIONS, 1);
        $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $performer->Id);

        $game->notify->all("message", clienttranslate('${player_name} may perform another action with ${performer_name}.'), [
            "player_name" => $game->getPlayerNameById($performer->ControllerId),
            "performer_name" => $performer->Name,
        ]);
    }

    private function finishLook(Game $game, ?int $addCardId): void
    {
        $owner = $this->getOwningCard($game->theah);
        $performerId = (int) $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            throw new UserException($game->translate("Performer not found."));
        }

        $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
        if (! is_array($originalCards))
        {
            $originalCards = [];
        }

        if ($addCardId !== null)
        {
            $addEvent = EventFactory::createCityCardAddedToLocationEvent($addCardId, $performer->Location);
            $game->theah->queueEvent($addEvent);
        }

        foreach ($originalCards as $peeked)
        {
            $id = (int) $peeked->id;
            if ($addCardId !== null && $id === $addCardId)
            {
                continue;
            }

            $sinkEvent = EventFactory::createCardAddedToCityDeckEvent($owner->ControllerId, $id, false);
            $game->theah->queueEvent($sinkEvent);
        }

        $this->grantLockedExtraAction($game, $performer);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
        $game->theah->queueEvent($actionResolvedEvent);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04037", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $performerId = (int) $game->globals->get(Game::CHOSEN_PERFORMER);
        $args["performerId"] = $performerId;

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04037)
        {
            $performer = $game->theah->getCharacterById($performerId);
            $cards = $this->getDiscardableCityCardsAtLocation($game->theah, $performer->Location);
            $args["ids"] = array_map(fn($card) => $card->Id, $cards);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04037_2)
        {
            $args["cards"] = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04037)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = (int) $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Invalid card id"));
            }

            if (! $card instanceof ICityDeckCard)
            {
                throw new UserException($game->translate("Card is not a City Card"));
            }

            if ($card->isControlled())
            {
                throw new UserException($game->translate("Card is controlled"));
            }

            if ($card->Location !== $performer->Location)
            {
                throw new UserException($game->translate("Card is not at the performer's location"));
            }

            if (! $card->canBeDiscardedFromCity())
            {
                throw new UserException($game->translate("Card cannot be discarded"));
            }

            // WHY: Peek before the discard event flushes. getCardsOnTopOfCityDeck reshuffles
            // City Discard when short — if we peeked after discard, the cost card could
            // shuffle back into the look.
            $peeked = $this->peekTopCityDeckCards($game, 5);
            $game->globals->set(Game::CHOSEN_CARD, json_encode($peeked));

            // WHY asEffect=false: discard is the printed cost (before the bullet), not the effect.
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent(
                $owner->ControllerId,
                $card->Id,
                $card->Location,
                $owner->Id,
                $asEffect = false
            );
            $game->theah->queueEvent($discardEvent);

            if (count($peeked) > 0)
            {
                $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at the top cards of the City Deck.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04037_2", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $this->grantLockedExtraAction($game, $performer);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $game->gamestate->nextState("cardDiscarded");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04037_2)
        {
            $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD, '[]'));
            $originalIds = array_map(fn($card) => (int) $card->id, is_array($originalCards) ? $originalCards : []);

            if (! in_array($id, $originalIds, true))
            {
                throw new UserException($game->translate("Card is not among the looked-at City Deck cards."));
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null || $card->Location != Game::LOCATION_CITY_DECK)
            {
                throw new UserException($game->translate("Card is not in the City Deck."));
            }

            $this->finishLook($game, $id);
            $game->gamestate->nextState("cardAdded");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04037_2)
        {
            // WHY: "You may add one" — Pass adds none, then still sink the rest + extra action.
            $this->finishLook($game, null);
            $game->gamestate->nextState("pass");
        }
    }
}
