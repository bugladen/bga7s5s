<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04005 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Character, Claim Location, Each Player Discards");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getDestroyTargets(Theah $theah, Character $performer): array
    {
        return array_values(array_filter(
            $theah->getCharactersAtLocation($performer->Location),
            fn(Character $character) => $character->Id != $performer->Id
                && $character->ControllerId == $performer->ControllerId
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: "Red Hand City Action" is a mechanical trait gate, not ISorcererAbility.
        // Full legality: another controlled character at location + location claimable
        // (Claim is a printed payoff — don't offer a dead character-destroy spend).
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Red Hand")
                && count($this->getDestroyTargets($theah, $performer)) > 0
                && $theah->canLocationBeClaimedBy($playerId, $performer->Location)
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || $performer->ControllerId != $event->playerId)
            {
                throw new UserException($game->translate("Invalid performer"));
            }

            if (! $performer->hasTrait("Red Hand"))
            {
                throw new UserException($game->translate("Performer must be a Red Hand."));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location."));
            }

            if (count($this->getDestroyTargets($event->theah, $performer)) == 0)
            {
                throw new UserException($game->translate("No other character you control at this location."));
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04005", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04005)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["schemeId"] = $owner->Id;
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getDestroyTargets($game->theah, $performer))
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

        if ($character->Id == $performer->Id)
        {
            return [false, $game->translate("Cannot destroy the performer")];
        }

        if ($character->ControllerId != $performer->ControllerId)
        {
            return [false, $game->translate("Character must be one you control")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character must be at the performer's location")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04005)
        {
            $owner = $this->getOwningCard($game->theah);
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

            $location = $performer->Location;

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} destroys ${target_inject_code}.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "target_inject_code" => $character->getInjectCode(),
            ]);

            // WHY: Direct destroy path (like Action_01018) unequips before destroy —
            // EventCharacterDestroyed recreates the card and does not auto-unequip.
            $character->unEquipAllAttachments($game->theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent($owner->ControllerId, $character->Id, $owner->getInjectCode());
            $game->theah->queueEvent($destroyEvent);

            if ($game->theah->canLocationBeClaimedBy($owner->ControllerId, $location))
            {
                $claimEvent = EventFactory::createLocationClaimedEvent($owner->ControllerId, $performerId, $location);
                $game->theah->queueEvent($claimEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                    'i18n' => ['location'],
                    'location' => $location,
                ]);
            }

            // WHY: ActionResolved (priority 3) before Transition (priority 8) — same as
            // Action_01095b. HD action wraps; discard is a trailing multi-player effect.
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $playersWithCards = [];
            foreach ($game->loadPlayersBasicInfos() as $playerId => $_)
            {
                $playerId = (int)$playerId;
                $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
                if (count($hand) > 0)
                {
                    $playersWithCards[] = $playerId;
                }
            }

            if (count($playersWithCards) > 0)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04005_2", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code}: No players have a card to discard.'), [
                    "scheme_inject_code" => $owner->getInjectCode(),
                ]);
            }

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04005_2)
        {
            $playerId = (int)$game->getCurrentPlayerId();
            $owner = $this->getOwningCard($game->theah);

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

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} discards a card.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
            ]);

            $game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
        }
    }
}
