<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04049 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Target Opposing Lower Influence May Engage or Must Move Adjacent");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $character) => $character->ModifiedInfluence < $performer->ModifiedInfluence
        ));
    }

    /**
     * @return list<string>
     */
    private function getAdjacentDestinations(Theah $theah, Character $target): array
    {
        return $theah->getAdjacentCityLocations($target->Location, $includeHome = false);
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => count($this->getValidTargets($theah, $performer)) > 0
        ));
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
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04049", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04049)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04049_2)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args["performerId"] = $performerId;
            $args["characterId"] = $targetId;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04049_3)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $args["performerId"] = $performerId;
            $args["characterId"] = $targetId;
            $args["locationIds"] = $target !== null
                ? $this->getAdjacentDestinations($game->theah, $target)
                : [];
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, $game->translate("Performer not found")];
        }

        if (! $character->isControlled() || $character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You must target an opposing character.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if ($character->ModifiedInfluence >= $performer->ModifiedInfluence)
        {
            return [false, $game->translate("Target must have lower Influence than your performer.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04049)
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

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} uses ${card_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // WHY already Engaged → skip Engage/Decline: print has no en-garde target filter
            // (unlike B.7 _04027). Mirror Duckfoot _01049 — auto-resolve the "if they do not" half.
            if ($character->Engaged)
            {
                $game->notify->all("message", clienttranslate('${character_inject_code} is already Engaged and must move to an adjacent City location.'), [
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "04049_3", $this->Id);
                $game->theah->queueEvent($transitionEvent);
            }
            else
            {
                $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "04049_2", $this->Id);
                $game->theah->queueEvent($transitionEvent);
            }

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04049_2)
        {
            if ($id != 1 && $id != 2)
            {
                throw new UserException($game->translate("Invalid action"));
            }

            $owner = $this->getOwningCard($game->theah);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            // Engage
            if ($id == 1)
            {
                $game->notify->all("message", clienttranslate('${player_name} decided to engage ${character_inject_code}'), [
                    "player_name" => $game->getPlayerNameById($target->ControllerId),
                    "character_inject_code" => $target->getInjectCode(),
                ]);

                $engageEvent = EventFactory::createCardEngagedEvent($target->ControllerId, $target->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);

                // WHY ActionResolved after opponent chooses: effect *is* engage-or-move (B.7).
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("done");
            }

            // Decline → adjacent City move chooser (same active player → direct transition)
            if ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} declined to engage ${character_inject_code}'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($target->ControllerId),
                    "character_inject_code" => $target->getInjectCode(),
                ]);

                $game->gamestate->nextState("declined");
            }
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04049_3)
        {
            $location = $ids[0];

            if ($game->theah->getCityLocation($location) == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $validDestinations = $this->getAdjacentDestinations($game->theah, $target);
            if (! in_array($location, $validDestinations))
            {
                throw new UserException($game->translate("Location must be an adjacent City location."));
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY engage=false: printed move has no Engage; initiating player = ability owner
            // (Confusion _03068) even though target's controller picks the destination.
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
                "player_name" => $game->getPlayerNameById($target->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
                "location_name" => $location,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
