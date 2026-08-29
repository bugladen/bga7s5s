<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04032_4 extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04032_4,
            // WHY: Not multiplayer — Giacinto's controller must see chooseList (revealed hand)
            // before the hand owner discards. Multi-ack + auto-ack pref / zombies skipped past
            // this UI immediately.
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04032_4",

            description: clienttranslate('Giacinto') . clienttranslate(': ${actplayer} is viewing the revealed hand.'),
            descriptionMyTurn: clienttranslate('Giacinto') . clienttranslate(': ${you} must acknowledge the revealed hand:'),
            transitions: [
                // WHY: Game bridge sets active player to hand owner before discard picker.
                "ok" => States::HIGH_DRAMA_PLAYER_TURN_04032_6,
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
    public function actPass(): void
    {
        // WHY: Do not use Game::actPass — that logs "passes". This is acknowledge-only.
        $this->game->gamestate->nextState("ok");
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("ok");
    }
}
