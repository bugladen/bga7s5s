<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01200_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01200_2,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01200_2",

            description: clienttranslate('${actplayer} is choosing a card for Crystal Eye\'s Forced Ability.'),
            descriptionMyTurn: clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose a card:'),
            transitions: [
                "" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
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
        // WHY: Prefer first Approach card; Pass only when chosen Approach empty
        // (unsticks Day-5 / old ZombieTrait for HD _2).
        $args = $this->game->argsForStatePrivate();
        $cards = $args['_private']['active']['args']['cards'] ?? [];
        if (!empty($cards)) {
            $this->game->actFromCardWithId((string)($cards[0]['id'] ?? $cards[0]['Id']));
        } else {
            $this->game->actFromCardPass();
        }
    }
}
