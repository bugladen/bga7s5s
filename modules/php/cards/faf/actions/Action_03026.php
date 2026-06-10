<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03026 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard a Card and Move to Adjacent Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        // "Action" — not "City Action", so can be used from home or city
        // But logically, moving to adjacent city only makes sense if in a city
        // However, reading the text: can discard + move from home to adjacent city

        // Has cards in hand to discard?
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        if (count($hand) == 0)
        {
            return false;
        }

        // Has valid adjacent city destinations?
        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        if (count($adjacentLocations) == 0)
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
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03026", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03026)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            $args['ids'] = array_values(array_map(fn($card) => $card->Id, $hand));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03026_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $adjacentLocations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            $args['locationIds'] = array_values($adjacentLocations);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03026_3)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $opposing = $game->theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId);
            $args['performerId'] = $owner->Id;
            $args['ids'] = array_values(array_map(fn(Character $c) => $c->Id, $opposing));
        }

        return $args;
    }

    private function isValidWoundCandidate(Character $owner, Character $character): bool
    {
        if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0)
        {
            return false;
        }

        return $character->Location == $owner->Location;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03026)
        {
            // Step 1: Discard a card from hand
            $cardToDiscard = $game->theah->getCardById($id);
            if ($cardToDiscard === null || $cardToDiscard->Location != Game::LOCATION_HAND)
            {
                throw new UserException($game->translate('Card must be in your hand.'));
            }

            // Store the discarded card id for step 2
            $game->globals->set(Game::CHOSEN_CARD, $id);

            $game->notify->all("message", clienttranslate('${player_name} discards ${card_inject_code}.'), [
                "player_name"       => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code"  => $cardToDiscard->getInjectCode(),
            ]);

            // Queue the discard event
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $owner->ControllerId,
                $id,
                $owner->Id,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            // Transition to step 2 (location picker)
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03026_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("cardDiscarded");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03026_3)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            if (! $this->isValidWoundCandidate($owner, $character))
            {
                throw new UserException($game->translate("Must wound an opposing character at Angeline's location."));
            }

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $character->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);

            EventFactory::createActionResolvedEvent($owner->ControllerId, $this->Id, $game->theah);

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03026_2)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $newLocation = $ids[0];
            $adjacentLocations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            if (! in_array($newLocation, $adjacentLocations))
            {
                throw new UserException($game->translate('Location must be adjacent to current location.'));
            }

            $discardedCardId = $game->globals->get(Game::CHOSEN_CARD);
            $discardedCard = $game->theah->getCardById($discardedCardId);

            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $newLocation,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($moveEvent);

            $game->notify->all("message", clienttranslate('${player_name} moves Angeline to ${location_name}.'), [
                "player_name"  => $game->getPlayerNameById($owner->ControllerId),
                "location_name" => $game->theah->getCityLocation($newLocation)->Name,
            ]);

            $game->globals->set(Game::CHOSEN_CARD, null);

            // If discarded card was a Sorcery and opponents are at the destination,
            // transition to step 3 to let the player choose which to wound.
            $needsTarget = $discardedCard !== null
                && $discardedCard->hasTrait("Sorcery")
                && count($game->theah->getOpposingCharactersAtLocation($newLocation, $owner->ControllerId)) > 0;

            if ($needsTarget)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03026_3", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                EventFactory::createActionResolvedEvent($owner->ControllerId, $this->Id, $game->theah);
            }

            $game->gamestate->nextState("done");
            return;
        }
    }

}
