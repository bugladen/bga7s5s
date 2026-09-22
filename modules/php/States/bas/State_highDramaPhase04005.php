<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04005 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04005,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04005",

            description: clienttranslate('Denounced, Disgraced') . clienttranslate(': ${actplayer} must choose another character they control to destroy.'),
            descriptionMyTurn: clienttranslate('Denounced, Disgraced') . clienttranslate(': ${you} must choose another character you control to destroy:'),
            // WHY: Named success — avoid nextState("") when sibling transitions exist.
            // WHY: back → DISPATCH (not bare CHOOSE_PERFORMER). stHighDramaInPlayActionDispatch
            // queues EventActionTriggered *before* branching to CHOOSE_PERFORMER; that event is
            // what Action_04005 handles to Transition("04005"). Bare back to CHOOSE_PERFORMER
            // leaves the second performer pick with no Triggered event → HD_EVENTS → next player.
            // DISPATCH is type=game and immediately lands on highDramaInPlayActionChoosePerformer.
            transitions: [
                "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_DISPATCH,
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actBack(): void
    {
        $this->game->actBack();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
