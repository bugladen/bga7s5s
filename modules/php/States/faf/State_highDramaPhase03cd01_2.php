<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase03cd01_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_03CD01_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase03cd01_2",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Penya') . clienttranslate(': ${you} must choose an adjacent City location:'),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_03CD01,
                "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
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
        $this->game->gamestate->nextState("back");
    }
}
