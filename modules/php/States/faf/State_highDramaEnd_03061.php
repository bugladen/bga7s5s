<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03061;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaEnd_03061 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_END_03061,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaEnd_03061",

            description: clienttranslate('Burn like Mice') . clienttranslate(': ${actplayer} must choose a character at Home to wound.'),
            descriptionMyTurn: clienttranslate('Burn like Mice') . clienttranslate(': ${you} must choose a character at Home to wound:'),
            transitions: [
                "" => States::HIGH_DRAMA_END_EVENTS,
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
        // WHY: Forced must still wound — pick the first remaining id so order is
        // arbitrary but every character still takes a wound across zombie turns.
        $sourceId = (int)$this->game->globals->get(Game::TRANSITION_SOURCE_ID, 0);
        $scheme = $this->game->theah->getCardById($sourceId);
        if ($scheme instanceof _03061 && count($scheme->remainingWoundIds) > 0)
        {
            $this->game->actFromCardWithId($scheme->remainingWoundIds[0]);
            return;
        }

        $this->game->gamestate->nextState();
    }
}
