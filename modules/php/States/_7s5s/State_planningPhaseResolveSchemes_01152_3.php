<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01152_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01152_3",

            description: clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} must choose an adjacent City Location to move the Renown TO.'),
            descriptionMyTurn: clienttranslate('Until Morale Improves') . clienttranslate(': ${you} must choose an adjacent City Location to move the Renown TO:'),
            transitions: [
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2,
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
    public function actBack(): void
    {
        $this->game->actBack();
    }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Old ZombieTrait used actPass("pass") for all three 01152 plan steps,
        // but this step has no "pass" transition — only back/locationChosen.
        // Escape via Back so we do not invent a TO location.
        $this->game->actBack();
    }
}
