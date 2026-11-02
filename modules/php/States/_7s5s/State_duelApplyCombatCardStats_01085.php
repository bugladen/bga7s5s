<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelApplyCombatCardStats_01085 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_APPLY_COMBAT_CARD_STATS_01085,
            type: StateType::ACTIVE_PLAYER,
            name: "duelApplyCombatCardStats_01085",

            description: clienttranslate('Porté Travel\'s Forced Ability: ${actplayer} is choosing their Sorcerer.'),
            descriptionMyTurn: clienttranslate('Porté Travel') . clienttranslate(': ${you} must choose a Sorcerer:'),
            transitions: [
                "" => States::DUEL_APPLY_COMBAT_CARD_STATS_EVENTS,
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

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        $this->game->gamestate->nextState();
    }
}
