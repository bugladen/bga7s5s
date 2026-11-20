<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05Hacked extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05HACKED,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05Hacked",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Stolen Schematics: ${actplayer} must choose a city location for Renown.',
            descriptionMyTurn: 'Stolen Schematics: ${you} must choose a city location to place Renown onto:',
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForState();
    }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        $locations = $this->game->theah->getCityLocations();
        if (count($locations) > 0)
        {
            $first = array_values($locations)[0];
            $this->game->actFromCardWithLocations(json_encode([$first->Name]));
            return;
        }

        $this->game->gamestate->nextState();
    }
}
