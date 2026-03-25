<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01180_3 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01180_3,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01180_3",

            // optional
            description: clienttranslate('${actplayer} may choose to Equip an Artifact from the top 4 cards of the City Deck.'),
            descriptionMyTurn: clienttranslate('${you} may choose to Equip an Artifact from the top 4 cards of the City Deck:'),
            transitions: [
                "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_01180_4,
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
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
        $this->game->actFromCardPass();
    }

}

