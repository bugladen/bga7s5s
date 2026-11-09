<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05William;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05William_4 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_4,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05William_4",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            // #{cost} is replaced client-side with a wealth chip (01167_4 idiom).
            description: 'Syrneth Secrets: ${actplayer} is paying to equip an Artifact.',
            descriptionMyTurn: 'Syrneth Secrets: ${you} must choose cards from Your Hand to pay #{cost} Wealth for chosen Artifact:',
            transitions: [
                "attachmentEquipped" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_3,
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
    public function actBack(): void
    {
        $this->game->actBack();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
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
