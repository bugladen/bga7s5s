<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04019 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04019,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04019",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('No More Words!') . clienttranslate(': ${you} must choose a Melee Weapon or Eisenfaust attachment to engage:'),
            transitions: [
                "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    public function zombie(int $playerId): void
    {
        $args = $this->game->argsForState();
        $attachments = $args["args"]["attachments"] ?? [];
        if (count($attachments) > 0)
        {
            $this->game->actFromCardWithId((string)$attachments[0]["id"]);
            return;
        }

        $this->game->gamestate->nextState("zombie");
    }
}
