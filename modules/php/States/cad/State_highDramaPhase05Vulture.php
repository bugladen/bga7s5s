<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Vulture extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05VULTURE,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Vulture",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing options to perform an Action.',
            descriptionMyTurn: 'Gusev Grabiezca' . ': ${you} must choose an opposing Scion or equipped character to wound:',
            transitions: [
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_DISPATCH,
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actBack(): void
    {
        $this->game->gamestate->nextState("back");
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
