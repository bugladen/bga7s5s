<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_03059_4 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_03059_4,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_03059_4",

            description: clienttranslate('${actplayer} is ordering Adversary Faction Deck cards.'),
            descriptionMyTurn: clienttranslate('Insightful') . clienttranslate(': ${you} must choose the order to replace the remaining Faction Deck cards: '),
            transitions: [
                "" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Remaining looked-at cards stay private until reorder completes.
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
