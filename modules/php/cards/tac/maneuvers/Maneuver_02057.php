<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02057 extends Maneuver implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Non-Participant at Location");
    }

    private function getValidTargets(Theah $theah): array
    {
        $actor = $theah->getDuelRoundActor();
        $adversary = $theah->getDuelRoundOpponent();
        $characters = $theah->getCharactersAtLocation($actor->Location);

        return array_values(array_filter($characters, fn($character) =>
            $character->Id != $actor->Id && $character->Id != $adversary->Id
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        return count($this->getValidTargets($theah)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "02057", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_02057)
        {
            $characters = $this->getValidTargets($game->theah);
            $args['characterIds'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $actor = $game->theah->getDuelRoundActor();
        $adversary = $game->theah->getDuelRoundOpponent();

        if ($character->Location != $actor->Location)
        {
            return [false, $game->translate("Character is not at this location")];
        }

        if ($character->Id == $actor->Id || $character->Id == $adversary->Id)
        {
            return [false, $game->translate("Character is a participant in the duel")];
        }

        return [true, ""];
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_02057)
        {
            $character = $game->theah->getCharacterById($id);
            if (! $character)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} wounds ${character_inject_code}.'), [
                'card_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $character->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            if ($character->hasTrait("Brute"))
            {
                $game->notify->all("message", clienttranslate('${card_inject_code}: ${character_inject_code} has Brute. Wounding again.'), [
                    'card_inject_code' => $owner->getInjectCode(),
                    'character_inject_code' => $character->getInjectCode(),
                ]);

                $woundEvent2 = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent2);
            }

            $game->gamestate->nextState();
        }
    }
}
