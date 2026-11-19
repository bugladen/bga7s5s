<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04029_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04029_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04029_2",

            description: clienttranslate('${actplayer} is choosing a character to equip an attachment to.'),
            descriptionMyTurn: clienttranslate('A Fine Addition...') . clienttranslate(': ${you} must choose a character at this location to equip the attachment to:'),
            transitions: [
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
        $args = $this->game->argsForState();
        $ids = $args["args"]["ids"] ?? [];
        if (count($ids) > 0)
        {
            $this->game->actFromCardWithId((string)$ids[0]);
            return;
        }

        $this->game->actFromCardPass();
    }
}
