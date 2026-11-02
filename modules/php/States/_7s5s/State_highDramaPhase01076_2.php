<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01076_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01076_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01076_2",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Blood Mark') . clienttranslate(': ${you} may Wound Performer and choose Character to include: '),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01076,
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named back/characterChosen only — bare nextState() invalid. Escape via Back.
        $this->game->actBack();
    }
}
