<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02001 extends CharacterAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Target Character to Andriana's Location and Issue Challenge");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $andriana = $this->getOwningCharacter($theah);
        if ( ! $theah->cardInCity($andriana))
        {
            return false;
        }

        if ($andriana->Engaged)
        {
            return false;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        $hand = array_filter($hand, fn($card) => $card->hasTrait("Sorcery"));
        if (count($hand) == 0)
        {
            return false;
        }

        $characters = $theah->getCharactersInPlay();
        $characters = array_filter($characters, fn($character) => $theah->cardInCity($character) && ! $character->hasTrait("Leader") && $character->isNotControlledByPlayer($playerId));

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $andriana = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($andriana->ControllerId, $andriana->Id, "02001", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02001_2)
        {
            $andriana = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $andriana->Id;

            $cardId = $game->globals->get(Game::CHOSEN_CARD);
            $args["discardedCardId"] = $cardId;

            $characters = $game->theah->getCharactersInPlay();
            $characters = array_filter($characters, fn($character) => $game->theah->cardInCity($character) && ! $character->hasTrait("Leader") && $character->isNotControlledByPlayer($andriana->ControllerId));
            $args["ids"] = array_map(fn($character) => $character->Id, array_values($characters));
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $andriana = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $andriana->ControllerId)
        {
            return [false, $game->translate("You cannot move yourself to your own location")];
        }

        if ($character->Location == $andriana->Location)
        {
            return [false, $game->translate("Character is already at Andriana's location")];
        }

        if (! $game->theah->cardInCity($character))
        {
            return [false, $game->translate("Character is not in the City")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02001)
        {
            $andriana = $this->getOwningCharacter($game->theah);

            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $andriana->ControllerId);
            $hand = array_filter($hand, fn($card) => $card->Id == $card->Id);
            if (count($hand) == 0)
            {
                throw new UserException($game->translate("Card not found in hand"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $card->Id);

            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02001_2)
        {
            $andriana = $this->getOwningCharacter($game->theah);

            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::ANDRIANA_DONDOLOS_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($andriana->ControllerId, $andriana->Id, $this->Id, $andriana->Id);
            $game->theah->queueEvent($sorceryStartEvent);

            $discardedCardId = $game->globals->get(Game::CHOSEN_CARD);
            $discardedCardEvent = EventFactory::createCardDiscardedFromHandEvent($andriana->ControllerId, $discardedCardId, $andriana->Id);
            $game->theah->queueEvent($discardedCardEvent);

            $engageEvent = EventFactory::createCardEngagedEvent($andriana->ControllerId, $andriana->Id, $andriana->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $moveEvent = EventFactory::createCardMovingEvent($andriana->ControllerId, $character->Id, $character->Location, $andriana->Location, false, $andriana->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($andriana->ControllerId, $andriana->Id, $this->Id, $andriana->Id);
            $game->theah->queueEvent($sorceryPlayedEvent);

            $transitionEvent = EventFactory::createTransitionEvent($andriana->ControllerId, $andriana->Id, "02001_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $this->announceAction($game);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            //createActionResolvedEvent not needed because the challenge will issue it

            $game->gamestate->nextState("characterChosen");
        }
    }
}