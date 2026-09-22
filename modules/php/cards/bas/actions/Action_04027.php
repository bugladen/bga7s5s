<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04027 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Target Opposing Non-Leader May Engage or You Claim");
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
            fn(Character $character) => ! $character->hasTrait("Leader") && ! $character->Engaged
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
                // WHY En Garde Diplomat Action: En Garde = precondition (not Engage cost);
                // Diplomat = mechanical trait gate (not Sorcerer).
                if ($performer->Engaged || ! $performer->hasTrait("Diplomat"))
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
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04027", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04027)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04027_2)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args["performerId"] = $performerId;
            $args["characterId"] = $targetId;
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

        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("You cannot target a Leader.")];
        }

        if ($character->Engaged)
        {
            return [false, $game->translate("Target must be en garde.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04027)
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

            $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "04027_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04027_2)
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

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
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
            }

            // Decline → claim this location
            if ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} declined to engage ${character_inject_code}'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($target->ControllerId),
                    "character_inject_code" => $target->getInjectCode(),
                ]);

                $location = $performer->Location;
                // WHY at emit only: do not grey the Action when unclaimable — engage may still happen;
                // same discipline as Censure _03057 claim-on-refuse.
                if ($game->theah->cardInCity($performer) && $game->theah->canLocationBeClaimedBy($performer->ControllerId, $location))
                {
                    $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} claims ${location_name}.'), [
                        "i18n" => ["location_name"],
                        "card_inject_code" => $owner->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($performer->ControllerId),
                        "location_name" => $location,
                    ]);

                    $claimEvent = EventFactory::createLocationClaimedEvent(
                        $performer->ControllerId,
                        $performer->Id,
                        $location
                    );
                    $game->theah->queueEvent($claimEvent);
                }
                else
                {
                    $game->notify->all("message", clienttranslate('${card_inject_code}: ${location_name} cannot be claimed.'), [
                        "i18n" => ["location_name"],
                        "card_inject_code" => $owner->getInjectCode(),
                        "location_name" => $location,
                    ]);
                }
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("done");
        }
    }
}
