<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01016_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01016_3",

            description: clienttranslate('Plans Within Plans') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
            descriptionMyTurn: clienttranslate('Plans Within Plans') . clienttranslate(': ${you} must must acknowlege revealed card:'),
            transitions: [
                "multipleOk" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Initiator already saw the reveal; others must Ok unless Auto-Acknowledge (pref 110).
        $this->game->stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer();
    }

    public function getArgs(): array
    {
        return $this->game->argsPlanningPhaseResolveSchemes_01016_3();
    }

    #[PossibleAction]
    public function actMultipleOk(): void
    {
        $this->game->actMultipleOk();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }
}
