<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Serenissima extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Serenissima",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing options to perform an Action.',
            descriptionMyTurn: 'Patricia Moustakas: ${you} must choose a Bureaucracy or Discovery in your discard pile to send to The Locker:',
            transitions: [
                "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA_2,
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
