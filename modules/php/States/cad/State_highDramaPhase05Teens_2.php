<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Teens_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05TEENS_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Teens_2",

            // WHY: Prototype CAD - plain strings, no clienttranslate (hide from translators).
            // WHY no "back" transition: the performer was engaged at announce (irreversible).
            description: '${actplayer} is choosing options to perform an Action.',
            descriptionMyTurn: 'Smash and Grab: ${you} must choose an attachment to destroy:',
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
