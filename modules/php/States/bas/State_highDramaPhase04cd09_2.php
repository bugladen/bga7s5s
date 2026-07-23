<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
class State_highDramaPhase04cd09_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        // WHY static copy: GameState may be constructed before COST_MODE is set.
        // Client branches on args.costMode for engage vs discard UI.
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04CD09_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04cd09_2",
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Knives Out') . clienttranslate(': ${you} must engage a performer or discard a card:'),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "back" => States::HIGH_DRAMA_PLAYER_TURN_04CD09,
                "costPaid" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, // engage/discard via EVENTS then 04cd09_3
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
