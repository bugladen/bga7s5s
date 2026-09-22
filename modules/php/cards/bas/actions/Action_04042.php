<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04042 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Target Opposing Engaged Character to a City Location You Do Not Control");
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $owner): array
    {
        // WHY: LOCATION_PLAYER_HOME is shared across players — opposing-at-Home falsely
        // includes enemies at their Homes (Benci / Axelle Home short-circuit).
        if ($owner->Location == Game::LOCATION_PLAYER_HOME || ! $theah->cardInCity($owner))
        {
            return [];
        }

        $opposing = $theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $character) => $character->Engaged
                && count($this->getValidDestinations($theah, $owner, $character)) > 0
        ));
    }

    /**
     * City locations whose controller is not the acting player (includes uncontrolled).
     *
     * @return list<string>
     */
    private function getValidDestinations(Theah $theah, Character $owner, Character $target): array
    {
        $destinations = [];
        foreach ($theah->getCityLocations() as $cityLocation)
        {
            $name = $cityLocation->Name;
            // WHY exclude current: "Move … to" is a no-op on the same spot.
            if ($name === $target->Location)
            {
                continue;
            }

            $controller = $theah->game->getControllerForLocation($name);
            if ($controller != $owner->ControllerId)
            {
                $destinations[] = $name;
            }
        }
        return $destinations;
    }

    private function controlsArtifactAtLocation(Theah $theah, Character $owner): bool
    {
        $playerId = $owner->ControllerId;

        foreach ($theah->getCharactersAtLocationByPlayerId($owner->Location, $playerId) as $character)
        {
            foreach ($character->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if ($attachment instanceof Attachment
                    && ! $attachment->FakeAttachment
                    && $attachment->hasTrait("Artifact")
                    && $attachment->ControllerId == $playerId)
                {
                    return true;
                }
            }
        }

        foreach ($theah->getAvailableAttachmentsAtLocation($owner->Location) as $attachment)
        {
            if ($attachment instanceof Attachment
                && ! $attachment->FakeAttachment
                && $attachment->hasTrait("Artifact")
                && $attachment->isControlled()
                && $attachment->ControllerId == $playerId)
            {
                return true;
            }
        }

        return false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        // WHY En Garde: precondition only — not an Engage cost (trichotomy c).
        if ($owner->Engaged)
        {
            return false;
        }

        if (! $this->controlsArtifactAtLocation($theah, $owner))
        {
            return false;
        }

        return count($this->getValidTargets($theah, $owner)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04042", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04042)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
            $args['ids'] = array_values(array_map(
                fn(Character $character) => $character->Id,
                $this->getValidTargets($game->theah, $owner)
            ));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04042_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $args['performerId'] = $owner->Id;
            $args['characterId'] = $targetId;
            $args['locationIds'] = $target !== null
                ? $this->getValidDestinations($game->theah, $owner, $target)
                : [];
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);

        if ($owner->Location == Game::LOCATION_PLAYER_HOME || ! $game->theah->cardInCity($owner))
        {
            return [false, $game->translate("Kaj Kousei must be in the City.")];
        }

        if ($character->Location != $owner->Location)
        {
            return [false, $game->translate("Character is not at the same location as Kaj Kousei")];
        }

        if (! $character->isControlled() || $character->ControllerId == $owner->ControllerId)
        {
            return [false, $game->translate("You must target an opposing character.")];
        }

        if (! $character->Engaged)
        {
            return [false, $game->translate("Character is not engaged")];
        }

        if (count($this->getValidDestinations($game->theah, $owner, $character)) == 0)
        {
            return [false, $game->translate("There is no City location you do not control.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04042)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $owner = $this->getOwningCharacter($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} uses ${card_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04042_2)
        {
            $location = $ids[0];

            if ($game->theah->getCityLocation($location) == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $owner = $this->getOwningCharacter($game->theah);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $validDestinations = $this->getValidDestinations($game->theah, $owner, $target);
            if (! in_array($location, $validDestinations, true))
            {
                throw new UserException($game->translate("Location must be a City location you do not control."));
            }

            // WHY engage=false: En Garde is only a precondition; printed text has no Engage cost.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $target->Id,
                $target->Location,
                $location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} moves ${character_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
                "location_name" => $location,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
