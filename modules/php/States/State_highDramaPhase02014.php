<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase02014 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_02014,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase02014",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate("Kaspar's Occupation") . clienttranslate(': ${you} may choose any cards to discard from the City Deck: '),
            transitions: [
                "allDiscarded" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "cardsChosen" => States::HIGH_DRAMA_PLAYER_TURN_02014_2,
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }

}
