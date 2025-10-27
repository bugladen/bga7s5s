<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01091 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01091,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01091",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Heal Wounds') . clienttranslate(': ${you} must choose one or two characters to heal wounds:'),
            transitions: [
                "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "charactersChosen" => States::HIGH_DRAMA_PLAYER_TURN_01091_2
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->actBack();
    }

}