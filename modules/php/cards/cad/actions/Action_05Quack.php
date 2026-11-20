<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Action: Target character at this location heals a wound • Their controller discards a card.
class Action_05Quack extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Target heals a wound; their controller discards a card";
    }

    /**
     * Controlled wounded characters at Takama's location whose controller has ≥1 hand card.
     * WHY controller+hand gate: right of • is mandatory discard — empty hand softlocks step 2.
     *
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $owner): array
    {
        $targets = [];
        foreach ($theah->getCharactersAtLocation($owner->Location) as $character)
        {
            if (! ($character instanceof Character))
            {
                continue;
            }

            if (! $character->isControlled())
            {
                continue;
            }

            if ($character->Wounds <= 0)
            {
                continue;
            }

            if ($theah->game->characterIsInDiscardOrLocker($character))
            {
                continue;
            }

            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $character->ControllerId);
            if (count($hand) === 0)
            {
                continue;
            }

            $targets[] = $character;
        }

        return $targets;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || $theah->game->characterIsInDiscardOrLocker($owner))
        {
            return false;
        }

        // WHY: Plain Action (not City) — Home is legal. Only need a completable heal+discard pair.
        return count($this->getValidTargets($theah, $owner)) > 0;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);
        if ($owner === null)
        {
            return [false, "Invalid action owner."];
        }

        if (! $character->isControlled())
        {
            return [false, "Target must be controlled."];
        }

        if ($character->Location != $owner->Location)
        {
            return [false, "Target must be at Takama's location."];
        }

        if ($character->Wounds <= 0)
        {
            return [false, "Character is not wounded."];
        }

        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $character->ControllerId);
        if (count($hand) === 0)
        {
            return [false, "Their controller has no cards to discard."];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Quack", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);
        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05QUACK)
        {
            $args['performerId'] = $owner->Id;
            // WHY array_values: non-sequential keys JSON-encode as objects; ids.forEach breaks.
            $args['ids'] = array_values(array_map(
                fn(Character $character) => $character->Id,
                $this->getValidTargets($game->theah, $owner)
            ));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05QUACK_2)
        {
            $args['performerId'] = $owner->Id;
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args['targetId'] = $targetId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05QUACK)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException("Character not found.");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCharacter($game->theah);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);

            $game->notify->all("message", '${owner_inject_code}: ${player_name} targets ${character_inject_code} to heal a wound.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            // WHY: Heal before discard (printed L→R). Route discard via EVENTS so heal
            // flushes first and createTransitionEvent can set active = target's controller
            // (Giacinto 04032 opponent-hand shape — local nextState cannot change active).
            $healEvent = EventFactory::createCharacterBeingHealedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($healEvent);

            $transition = EventFactory::createTransitionEvent(
                $target->ControllerId,
                $owner->Id,
                "05Quack_2",
                $this->Id
            );
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("characterChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05QUACK_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException("Target character not found.");
            }

            $playerId = (int)$game->getActivePlayerId();
            if ($playerId !== $target->ControllerId)
            {
                throw new UserException("It is not your turn to discard.");
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null)
            {
                throw new UserException("Card not found.");
            }

            if ($card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId)
            {
                throw new UserException("Card is not in your hand.");
            }

            $game->notify->all("message", '${owner_inject_code}: ${player_name} discards ${card_inject_code}.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $playerId,
                $id,
                $owner->Id,
                false,
                false,
                true
            );
            $game->theah->queueEvent($discardEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("");
        }
    }
}
