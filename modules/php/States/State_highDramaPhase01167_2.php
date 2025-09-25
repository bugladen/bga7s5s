<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01167_2 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01167_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01167_2",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Liberating Goods') . clienttranslate(': ${you} must choose a Non-Unique Attachment to Equip from chosen discard pile: '),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01167,
<<<<<<< HEAD
                "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01167_3,
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
=======
                "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01167_3
>>>>>>> 4c39f1dba8ce4667ded62c4614ff8329d676af67
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
    public function actBack(): void
    {
        $this->game->actBack();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

<<<<<<< HEAD
    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

=======
>>>>>>> 4c39f1dba8ce4667ded62c4614ff8329d676af67
    public function zombie(int $playerId): void
    {
        $this->game->actBack();
    }

}