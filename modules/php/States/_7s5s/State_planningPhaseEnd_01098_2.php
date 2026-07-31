<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseEnd_01098_2 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_END_01098_2,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "planningPhaseEnd_01098_2",

            // optional
            description: clienttranslate('The Cat\'s Embargo') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
            descriptionMyTurn: clienttranslate('The Cat\'s Embargo') . clienttranslate(': ${you} must must acknowlege revealed card:'),
            transitions: [
                "multipleOk" => States::PLANNING_PHASE_END_EVENTS
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        $id = $this->game->globals->get(GAME::CHOSEN_CARD);
        $card = $this->game->getCardObjectFromDb($id);
        return [
            "card" => $card->getPropertyArray($this->game)
        ];
    } 

    public function onEnteringState(int $activePlayerId) 
    {
        $this->game->stMultiPlayerInitCardRevealAcknowledge();
    }
    
    #[PossibleAction]
    public function actMultipleOk(): void
    {
        $this->game->actMultipleOk();
    }

    public function zombie(int $playerId): void
    {
        $this->game->actMultipleOk();
    }

}