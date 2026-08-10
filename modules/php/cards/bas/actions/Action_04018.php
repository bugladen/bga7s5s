<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04018 extends RiskAction implements IAbilityThatTargetsCharacters
{
    // WHY: Risk is in discard by resolve time; buildCity loads discard so this
    // sticky list still drives State_04018_2 multiactive (same seat as Action_04005).
    /** @var list<int> */
    public array $PlayersToDiscard = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move to Adjacent Enemy; Sorcerer/Monster Controllers Discard");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $adjacentLocations = $theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
        $targets = [];
        foreach ($adjacentLocations as $location)
        {
            $characters = $theah->getCharactersAtLocation($location);
            foreach ($characters as $character)
            {
                if ($character->isControlled() && $character->ControllerId != $performer->ControllerId)
                {
                    $targets[] = $character;
                }
            }
        }

        return $targets;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah) {
                // WHY En Garde Action label: performer must already be ready — not an Engage cost.
                if ($performer->Engaged)
                {
                    return false;
                }

                return count($this->getValidTargets($theah, $performer)) > 0;
            }
        ));
    }

    /**
     * @return list<int>
     */
    private function getPlayersWhoMustDiscard(Theah $theah, string $location, int $actingPlayerId): array
    {
        $playerIds = [];
        $characters = $theah->getCharactersAtLocation($location);
        foreach ($characters as $character)
        {
            if ($character->ControllerId == 0 || $character->ControllerId == $actingPlayerId)
            {
                continue;
            }
            if ($character->hasTrait("Sorcerer") || $character->hasTrait("Monster"))
            {
                $playerIds[$character->ControllerId] = $character->ControllerId;
            }
        }

        $playersToDiscard = [];
        foreach ($playerIds as $playerId)
        {
            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
            if (count($hand) > 0)
            {
                $playersToDiscard[] = $playerId;
            }
        }

        return $playersToDiscard;
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

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        // WHY: Action-only Risk — combat-card pay has no Maneuver discount channel.
        // Do not invent a Maneuver solely to carry getManeuverFromCombatCardDiscount.
        if ($action->Id == $this->Id)
        {
            if ($performer === null)
            {
                return $discount;
            }

            if ($performer->hasTrait("Academic") || $performer->hasTrait("Hunter"))
            {
                $discount += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf(
                    $theah->game->translate("%s: -1 because your performer is an Academic or Hunter."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->PlayersToDiscard = [];
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04018", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04018)
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

        if (! $character->isControlled() || $character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You must target an enemy character.")];
        }

        $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
        if (! in_array($character->Location, $adjacentLocations))
        {
            return [false, $game->translate("Character must be at an adjacent City location.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04018)
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

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            $owner = $this->getOwningCard($game->theah);
            $destination = $character->Location;

            $moveEvent = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $destination,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            // WHY: ActionResolved (priority 3) before Transition (priority 8) — same as
            // Action_04005 / Action_01095b. HD action wraps; discard is a trailing multi-player effect.
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->PlayersToDiscard = $this->getPlayersWhoMustDiscard(
                $game->theah,
                $destination,
                $performer->ControllerId
            );
            $owner->IsUpdated = true;

            if (count($this->PlayersToDiscard) > 0)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04018_2", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${card_inject_code}: No other player who controls a Sorcerer or Monster there has a card to discard.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                ]);
            }

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04018_2)
        {
            $playerId = (int)$game->getCurrentPlayerId();
            $owner = $this->getOwningCard($game->theah);

            if (! in_array($playerId, $this->PlayersToDiscard))
            {
                throw new UserException($game->translate("You are not required to discard a card."));
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
            $hand = array_filter($hand, fn($handCard) => $handCard->Id == $id);
            if (count($hand) == 0)
            {
                throw new UserException($game->translate("Card is not in your hand"));
            }

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $playerId,
                $card->Id,
                $owner->Id,
                false,
                false,
                true
            );
            $game->theah->queueEvent($discardEvent);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} discards a card.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
            ]);

            $game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
        }
    }
}
