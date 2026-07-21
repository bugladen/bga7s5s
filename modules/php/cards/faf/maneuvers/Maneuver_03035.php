<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03035 extends Maneuver implements IAbilityThatTargetsCharacters
{
    private bool $ChooseRiposte = false;
    private int $WoundTargetId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Other Character; +1 Riposte or +2 Thrust");
        $this->ChooseRiposte = false;
        $this->WoundTargetId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        return count($this->getOtherCharactersAtDuelLocation($theah, $playerId)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            // WHY: stackEvent so the character/choice prompts fire before calc (Pattern C.3).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03035", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($this->ChooseRiposte)
            {
                $event->riposte += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());
            }
            else
            {
                $event->thrust += 2;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Thrust."), $owner->getInjectCode());
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->ChooseRiposte = false;
            $this->WoundTargetId = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03035)
        {
            $actor = $game->theah->getDuelRoundActor();
            $characters = $this->getOtherCharactersAtDuelLocation($game->theah, $actor->ControllerId);
            $args['ids'] = array_map(fn(Character $character) => $character->Id, $characters);
            $args['performerId'] = $actor->Id;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_03035_2)
        {
            $actor = $game->theah->getDuelRoundActor();
            $args['performerId'] = $actor->Id;
            $args['targetId'] = $this->WoundTargetId;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $actor = $game->theah->getDuelRoundActor();

        if ($character->Id == $actor->Id)
        {
            return [false, $game->translate("You cannot wound your participant.")];
        }

        if ($character->ControllerId != $actor->ControllerId)
        {
            return [false, $game->translate("You may only wound a character you control.")];
        }

        if ($character->Location != $actor->Location)
        {
            return [false, $game->translate("Character must be at this location.")];
        }

        return [true, ""];
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03035)
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
            $this->WoundTargetId = $character->Id;
            $owner->IsUpdated = true;

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} chooses ${character_inject_code} to wound.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // WHY: stackEvent (not queueEvent) — ResolveManeuver + CalculateManeuverValues are still
            // pending from stResolveManeuverFromCombatCard. queueEvent would land behind calc and the
            // Riposte/Thrust choice would be too late (calc already applied the default).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03035_2", $this->Id);
            $game->theah->stackEvent($transition);

            $game->gamestate->nextState();
            return;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_03035_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $target = $game->theah->getCharacterById($this->WoundTargetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($id == 1)
            {
                $this->ChooseRiposte = true;
                $game->notify->all("message", clienttranslate('${card_inject_code} wounds ${character_inject_code} and adds 1 Riposte.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $target->getInjectCode(),
                ]);
            }
            else if ($id == 2)
            {
                $this->ChooseRiposte = false;
                $game->notify->all("message", clienttranslate('${card_inject_code} wounds ${character_inject_code} and adds 2 Thrust.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $target->getInjectCode(),
                ]);
            }
            else
            {
                throw new UserException($game->translate("Invalid choice"));
            }

            // WHY: queue wound from choice act (C.3 side-effect variant). Pending calc still follows
            // after nextState → EVENTS, so ChooseRiposte is set before EventDuelCalculateManeuverValues.
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);

            $owner->IsUpdated = true;
            $game->gamestate->nextState();
            return;
        }

        $game->gamestate->nextState();
    }

    /**
     * @return Character[]
     */
    private function getOtherCharactersAtDuelLocation(Theah $theah, int $playerId): array
    {
        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return [];
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($actor->Location, $playerId);
        return array_values(array_filter($characters, fn(Character $character) => $character->Id != $actor->Id));
    }
}
