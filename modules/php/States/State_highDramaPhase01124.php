<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01124 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01124,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01124",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Ved\'ma') . clienttranslate(': ${you} must choose a Sorcery Risk from your discard pile to play:'),
            transitions: [
                "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
                "actionChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
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
    public function actFromCardWithActionId(int $actionSourceId, string $actionId): void
    {
        $this->game->actFromCardWithActionId($actionSourceId, $actionId);
    }

    public function zombie(int $playerId): void
    {
        $this->game->actBack();
    }

}