<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04cd01b_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04cd01b_2",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Penya') . clienttranslate(': ${you} must choose a Risk to play from chosen discard pile:'),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_04CD01B,
                "actionChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actFromCardWithActionId(int $actionSourceId, string $actionId): void
    {
        $this->game->actFromCardWithActionId($actionSourceId, $actionId);
    }

    public function zombie(int $playerId): void
    {
        $this->game->actBack();
    }
}
