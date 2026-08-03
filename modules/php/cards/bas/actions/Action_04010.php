<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04010 extends RiskAction implements ISorcererAbility
{
    public string $ChosenDiscardPile = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sink cards from a discard pile, draw, sink this card");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            fn(Character $character) => $character->hasTrait("Sorcerer")
        ));
    }

    /**
     * Always list every player discard pile plus City Discard.
     * WHY: Printed text is "a single discard pile" — the chooser state must present
     * every legal pile, including empty ones (Pass on the next step sinks zero) and
     * City Discard (sinks to the City Deck, not a faction deck).
     *
     * @return list<array{id:int,name:string,location:string}>
     */
    private function getDiscardPiles(Game $game): array
    {
        $piles = [];
        $index = 0;
        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player)
        {
            $piles[] = [
                'id' => $index++,
                'name' => sprintf($game->translate("%s's Discard Pile"), $player['player_name']),
                'location' => $game->getPlayerDiscardDeckName($playerId),
            ];
        }

        $piles[] = [
            'id' => $index,
            'name' => $game->translate("City Discard Pile"),
            'location' => Game::LOCATION_CITY_DISCARD,
        ];

        return $piles;
    }

    private function resolveFinish(Game $game, array $sinkIds): void
    {
        $owner = $this->getOwningCard($game->theah);
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        $sorceryStart = EventFactory::createSorcererAbilityStartEvent(
            $owner->ControllerId,
            $owner->Id,
            $this->Id,
            $performer?->Id ?? 0
        );
        $game->theah->queueEvent($sorceryStart);

        $pile = $this->ChosenDiscardPile;
        foreach ($sinkIds as $cardId)
        {
            $card = $game->theah->getCardById($cardId);
            if ($card === null)
            {
                $card = $game->getCardObjectFromDb($cardId);
            }
            if ($card === null)
            {
                continue;
            }

            if ($pile === Game::LOCATION_CITY_DISCARD)
            {
                $removeEvent = EventFactory::createCardRemovedFromCityDiscardPileEvent($owner->ControllerId, $cardId);
                $game->theah->queueEvent($removeEvent);

                $sinkEvent = EventFactory::createCardAddedToCityDeckEvent($owner->ControllerId, $cardId, false);
                $game->theah->queueEvent($sinkEvent);
            }
            else
            {
                $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($card->OwnerId, $cardId);
                $game->theah->queueEvent($removeEvent);

                // WHY: Sink returns a card to its owner's faction deck bottom.
                $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($card->OwnerId, $cardId, false);
                $game->theah->queueEvent($sinkEvent);
            }
        }

        $drawEvent = EventFactory::createCardDrawnEvent(
            $owner->ControllerId,
            sprintf($game->translate("%s effect"), $owner->getInjectCode())
        );
        $game->theah->queueEvent($drawEvent);

        // Risk is already in discard from paying the in-hand Action.
        $removeSelf = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $owner->Id);
        $game->theah->queueEvent($removeSelf);

        $sinkSelf = EventFactory::createCardAddedToFactionDeckEvent($owner->ControllerId, $owner->Id, false);
        $game->theah->queueEvent($sinkSelf);

        $sorceryPlayed = EventFactory::createSorcererAbilityPlayedEvent(
            $owner->ControllerId,
            $owner->Id,
            $this->Id,
            $performer?->Id ?? 0
        );
        $game->theah->queueEvent($sorceryPlayed);

        $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
        $game->theah->queueEvent($resolved);

        $this->ChosenDiscardPile = '';
        $owner->IsUpdated = true;
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
            // WHY: Always enter the pile-chooser state — never auto-pick a single pile
            // or skip when piles look empty. City Discard must be choosable every time.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04010", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);
        $owner = $this->getOwningCard($game->theah);
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $args['performerId'] = $performerId;

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04010)
        {
            $args['piles'] = $this->getDiscardPiles($game);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04010_2)
        {
            $pile = $this->ChosenDiscardPile;
            $cards = $game->theah->getCardObjectsAtLocation($pile);
            if ($pile !== Game::LOCATION_CITY_DISCARD)
            {
                // WHY: This Risk is already in the owner's discard from play — do not sink it
                // as one of the "up to two" (separate "sink this card" step).
                $cards = array_filter($cards, fn($card) => $card->Id != $owner->Id);
            }
            $args['cards'] = array_values(array_map(fn($card) => $card->getPropertyArray($game), $cards));

            $label = $game->translate("Discard Pile");
            if ($pile === Game::LOCATION_CITY_DISCARD)
            {
                $label = $game->translate("City Discard Pile");
            }
            else
            {
                $players = $game->loadPlayersBasicInfos();
                foreach ($players as $playerId => $player)
                {
                    if ($game->getPlayerDiscardDeckName($playerId) === $pile)
                    {
                        $label = sprintf($game->translate("%s's Discard Pile"), $player['player_name']);
                        break;
                    }
                }
            }
            $args['discardPileLabel'] = $label;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04010)
        {
            $owner = $this->getOwningCard($game->theah);
            $piles = $this->getDiscardPiles($game);
            $chosen = null;
            foreach ($piles as $pile)
            {
                if ($pile['id'] === $id)
                {
                    $chosen = $pile;
                    break;
                }
            }
            if ($chosen === null)
            {
                throw new UserException($game->translate("Invalid discard pile."));
            }

            $this->ChosenDiscardPile = $chosen['location'];
            $owner->IsUpdated = true;

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04010_2", $this->Id);
            $game->theah->queueEvent($transition);
            $game->gamestate->nextState("pileChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04010_2)
        {
            if (count($ids) > 2)
            {
                throw new UserException($game->translate("You may sink at most two cards."));
            }

            $owner = $this->getOwningCard($game->theah);
            $pile = $this->ChosenDiscardPile;
            if ($pile === '')
            {
                throw new UserException($game->translate("No discard pile selected."));
            }

            foreach ($ids as $cardId)
            {
                $card = $game->theah->getCardById($cardId);
                if ($card === null)
                {
                    $card = $game->getCardObjectFromDb($cardId);
                }
                if ($card === null || $card->Location != $pile)
                {
                    throw new UserException($game->translate("Card is not in the chosen discard pile."));
                }
                if ($card->Id == $owner->Id)
                {
                    throw new UserException($game->translate("You cannot choose this card as one of the sunk discard cards."));
                }
            }

            $this->resolveFinish($game, $ids);
            $game->gamestate->nextState("cardsChosen");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04010_2)
        {
            // Pass = sink zero cards, then draw and sink this card.
            $this->resolveFinish($game, []);
            $game->gamestate->nextState("cardsChosen");
        }
    }
}
