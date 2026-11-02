<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01152_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01152_2",

            description: clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} must choose a City Location to move a Renown FROM, if able.'),
            descriptionMyTurn: clienttranslate('Until Morale Improves') . clienttranslate(': ${you} must choose a City Location to move a Renown FROM, if able: '),
            transitions: [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsEmpty();
    }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named pass/locationChosen only. actPass("pass") exits to EVENTS
        // (old ZombieTrait) without picking a FROM location.
        $this->game->actPass("pass");
    }
}
