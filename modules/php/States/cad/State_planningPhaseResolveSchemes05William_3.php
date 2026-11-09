<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05William;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05William_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_3,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05William_3",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Syrneth Secrets: ${actplayer} may equip an available Artifact at -1 cost.',
            descriptionMyTurn: 'Syrneth Secrets: ${you} may equip an available Artifact at -1 cost:',
            transitions: [
                "artifactChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "zombie" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Clear scheme -1 arm so a later HD Artifact equip does not keep the planning discount.
        $scheme = $this->game->theah->getCardById((int)$this->game->globals->get(Game::TRANSITION_SOURCE_ID, 0));
        if ($scheme instanceof _05William)
        {
            $scheme->setPlanningEquipDiscountActive($this->game, false);
        }
        $this->game->gamestate->nextState("zombie");
    }
}
