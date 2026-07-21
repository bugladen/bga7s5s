<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03071 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage an Opposing Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $this->locationControlledByOpponent($theah, $performer)
                && count($this->getValidEngageCharacters($theah, $performer)) > 0
        ));
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        // WHY: Pattern E Leader discount on Action-only Risk — mirror Action_01159 / Action_01160.
        // No Maneuver printed, so combat-card pay stays full WealthCost (same as those exemplars).
        if ($action->Id == $this->Id)
        {
            $owner = $this->getOwningCard($theah);
            $leader = $theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader !== null && ($leader->hasTrait('Villain') || $leader->hasTrait('Pirate')))
            {
                $discount += 1;
                $explanations[] = sprintf(
                    $theah->game->translate("%s: -1 because Leader is a Villain or Pirate."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function isValidEngageCharacter(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Character must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character must be at your performer's location.")];
        }

        if ($character->Engaged)
        {
            return [false, $game->translate("Character is already engaged.")];
        }

        if (! $this->locationControlledByOpponent($game->theah, $performer))
        {
            return [false, $game->translate("This location is not controlled by an opponent.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03071", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03071)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performer->Id;
            $args['ids'] = array_map(
                fn(Character $c) => $c->Id,
                $this->getValidEngageCharacters($game->theah, $performer)
            );
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03071)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidEngageCharacter($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId,
                $character->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }

    private function locationControlledByOpponent(Theah $theah, Character $performer): bool
    {
        $controller = $theah->game->getControllerForLocation($performer->Location);
        return $controller != 0 && $controller != $performer->ControllerId;
    }

    private function getValidEngageCharacters(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        return array_values(array_filter($opposing, fn(Character $c) => ! $c->Engaged));
    }
}
