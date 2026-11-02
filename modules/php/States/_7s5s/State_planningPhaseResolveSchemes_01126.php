<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01126 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01126",

            description: clienttranslate('Leshiye of the Wood: ${actplayer} must choose options for Leshiye of the Wood.'),
            descriptionMyTurn: clienttranslate('Leshiye of the Wood: ${you} must choose an outer City Location for Leshiye of the Wood:'),
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2,
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS, // Zombie mode will pass here
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
        // WHY: Named locationChosen/pass only. Prefer first legal outer location so
        // the scheme progresses; pass only if args are empty (old ZombieTrait).
        $args = $this->game->argsForState();
        $locationIds = $args['args']['locationIds'] ?? [];
        if (!empty($locationIds)) {
            $this->game->actFromCardWithLocations(json_encode([$locationIds[0]]));
        } else {
            $this->game->actPass("pass");
        }
    }
}
