<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelEndOfRound_01200 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_END_OF_ROUND_01200,
            type: StateType::ACTIVE_PLAYER,
            name: "duelEndOfRound_01200",

            description: clienttranslate('${actplayer} is choosing an opponent for Crystal Eye\'s Forced Ability.'),
            descriptionMyTurn: clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose an opponent:'),
            transitions: [
                "opponentChosen" => States::DUEL_END_OF_ROUND_01200_2,
                "pass" => States::DUEL_END_OF_ROUND_EVENTS,
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
        // WHY: Day-5 empty Approach → Pass; otherwise pick first legal opponent
        // (Crystal Eye Forced soft-lock fix / old ZombieTrait).
        $args = $this->game->argsForState();
        $opponents = $args['args']['opponents'] ?? [];
        if (!empty($opponents)) {
            $this->game->actFromCardWithId((string)($opponents[0]['id'] ?? $opponents[0]['Id']));
        } else {
            $this->game->actFromCardPass();
        }
    }
}
