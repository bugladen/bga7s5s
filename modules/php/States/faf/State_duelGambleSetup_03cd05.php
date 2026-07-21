<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelGambleSetup_03cd05 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_GAMBLE_SETUP_03CD05,
            type: StateType::ACTIVE_PLAYER,
            name: "duelGambleSetup_03cd05",

            description: clienttranslate('${actplayer} is choosing options from Devil Jonah\'s Bones.'),
            descriptionMyTurn: clienttranslate('Devil Jonah\'s Bones') . clienttranslate(': ${you} may reveal Gamble cards from the bottom of your deck instead of the top:'),
            transitions: [
                "" => States::DUEL_GAMBLE_SETUP_EVENTS,
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
        $this->game->gamestate->nextState();
    }
}
