<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01126_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01126_2",

            description: clienttranslate('Leshiye of the Wood: ${actplayer} must choose options for Leshiye of the Wood.'),
            descriptionMyTurn: clienttranslate('Leshiye of the Wood: ${you} must choose two other locations to place Renown onto:'),
            transitions: [
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
                "locationsChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
        // WHY: Named back/locationsChosen only. Place requiredCount locations when
        // available so the scheme resolves; otherwise actBack (old ZombieTrait).
        $args = $this->game->argsForState();
        $locationIds = $args['args']['locationIds'] ?? [];
        $requiredCount = $args['args']['requiredLocationCount'] ?? min(2, count($locationIds));
        if ($requiredCount > 0 && count($locationIds) >= $requiredCount) {
            $this->game->actFromCardWithLocations(json_encode(array_slice($locationIds, 0, $requiredCount)));
        } else {
            $this->game->actBack();
        }
    }
}
