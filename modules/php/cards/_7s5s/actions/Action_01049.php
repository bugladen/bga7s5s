<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01049 extends AttachmentAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Character If They Do Not Engage");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $owner = $this->getOwningAttachment($theah);

        if ($owner->Engaged)
        {
            return false;
        }

        $owningCharacter = $this->getOwningCharacter($theah);
        if ( ! $theah->cardInCity($owningCharacter))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($owningCharacter->Location);
        $characters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($owningCharacter->ControllerId));

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningAttachment($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01049", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01049)
        {
            $owningCharacter = $this->getOwningCharacter($game->theah);
            $characters = $game->theah->getCharactersAtLocation($owningCharacter->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($owningCharacter->ControllerId)));
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningAttachment($game->theah);

        if (! $character->isControlled())
        {
            return [false, $game->translate("You cannot manipulate a character that is not controlled.")];
        }

        if ($character->ControllerId == $owner->ControllerId)
        {
            return [false, $game->translate("You cannot manipulate a character that you control.")];
        }

        if ($character->Location != $owner->Location)
        {
            return [false, $game->translate("You cannot manipulate a character that is not at the same location as the attachment.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01049)
        {
            $character = $game->theah->getCharacterById($id);
            if (! $character)
            {
                throw new UserException($game->translate("Invalid character"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningAttachment($game->theah);

            if ($character->Engaged)
            {
                $game->notify->all("message", clienttranslate('${player_name} has used the action of ${card_inject_code} and selected ${character_inject_code} as the target, who was already Engaged.'), [
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                    "card_inject_code" => $owner->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }
            else
            {
                $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

                $game->notify->all("message", clienttranslate('${player_name} has used the action of ${card_inject_code} and selected ${character_inject_code} as the target.'), [
                    'player_name' => $game->getPlayerNameById($owner->ControllerId),
                    'card_inject_code' => $owner->getInjectCode(),
                    'character_inject_code' => $character->getInjectCode(),
                ]);
    
                $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "01049_2", $this->Id);
                $game->theah->queueEvent($transitionEvent);
   
            }

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01049_2)
        {
            $targetCharacterId = $game->globals->get(Game::CHOSEN_TARGET);
            $targetCharacter = $game->theah->getCharacterById($targetCharacterId);
            $owner = $this->getOwningAttachment($game->theah);

            // Engage the target character
            if ($id == 1)
            {
                $game->notify->all("message", clienttranslate('${player_name} decided to engage ${character_inject_code}'), [
                    'player_name' => $game->getPlayerNameById($targetCharacter->ControllerId),
                    'character_inject_code' => $targetCharacter->getInjectCode(),
                ]);

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $targetCharacter->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }

            // Wound the target character
            if ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${player_name} decided to wound ${character_inject_code}'), [
                    'player_name' => $game->getPlayerNameById($targetCharacter->ControllerId),
                    'character_inject_code' => $targetCharacter->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($targetCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}