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

class Action_04038 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Target; Heal a Wound");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * Complete as much as possible: Engaged (can be en garded) or wounded (can heal).
     *
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $characters = $theah->getCharactersAtLocation($performer->Location);
        return array_values(array_filter(
            $characters,
            fn(Character $character) => $character->Engaged || $character->Wounds > 0
        ));
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
                // WHY Academic City Action: Academic = mechanical trait gate (not Sorcerer).
                // Not En Garde Action — performer does not need to be en garde.
                if (! $performer->hasTrait("Academic"))
                {
                    return false;
                }

                return count($this->getValidTargets($theah, $performer)) > 0;
            }
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04038", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04038)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
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

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if (! $character->Engaged && $character->Wounds == 0)
        {
            return [false, $game->translate("Target must be engaged or wounded.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04038)
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

            $owner = $this->getOwningCard($game->theah);

            $game->notify->all("message", clienttranslate('${player_name} uses ${card_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            if ($character->Engaged)
            {
                $engardeEvent = EventFactory::createCardEngardedEvent(
                    $character->ControllerId,
                    $character->Id,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($engardeEvent);
            }

            if ($character->Wounds > 0)
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent(
                    $character->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $game->theah->queueEvent($healEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
