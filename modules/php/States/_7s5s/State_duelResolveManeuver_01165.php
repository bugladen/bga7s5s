<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01165 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01165,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01165",

            description: clienttranslate('${actplayer} is choosing their Duel Action options.'),
            descriptionMyTurn: clienttranslate('I Know That Trick!') . clienttranslate(': ${you} must choose a Technique to copy:'),
            transitions: [
                "cardChosen" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
                "pass" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    #[PossibleAction]
    public function actPassWithPass(): void
    {
        $this->game->actPassWithPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named cardChosen/pass — decline copy via pass (old ZombieTrait nextState("pass")).
        $this->game->gamestate->nextState("pass");
    }
}
