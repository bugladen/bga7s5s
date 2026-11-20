<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Rum_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05RUM_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Rum_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            // #{cost} is replaced client-side with a wealth chip (Corncob_3 / 01167 idiom).
            description: '${actplayer} is paying to equip a Unique Attachment.',
            descriptionMyTurn: 'X Marks the Spot: ${you} must choose cards from Your Hand to pay #{cost} Wealth for chosen Attachment:',
            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
