<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01035_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01035_3,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01035_3",

            description: clienttranslate('${actplayer} is choosing whether to recruit the revealed mercenary.'),
            descriptionMyTurn: clienttranslate('Kaspar') . clienttranslate(': ${you} must choose whether to recruit the revealed mercenary:'),
            transitions: [
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "recruit" => States::HIGH_DRAMA_PLAYER_TURN_01035_4,
                "recruitNoParley" => States::HIGH_DRAMA_RECRUIT_ACTION_PAY_FOR_MERCENARY,
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
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named pass/recruit — decline recruit (sinks merc via actFromCardPass).
        $this->game->actFromCardPass();
    }
}
