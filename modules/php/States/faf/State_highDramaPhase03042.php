<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase03042 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_03042,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase03042",

            description: clienttranslate('When Least Expected') . clienttranslate(': ${actplayer} must discard a card to refuse the challenge.'),
            descriptionMyTurn: clienttranslate('When Least Expected') . clienttranslate(': ${you} must discard a card to refuse the challenge:'),
            transitions: [
                "cardDiscarded" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT,
                "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE,
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
        // WHY: Free refuse for zombies — avoid hanging if hand is empty mid-state.
        $performer = $this->game->getCardObjectFromDb($this->game->globals->get(Game::CHOSEN_PERFORMER));
        $target = $this->game->getCardObjectFromDb($this->game->globals->get(Game::CHOSEN_TARGET));

        $event = EventFactory::createChallengeRejectedEvent($performer->Id, $target->Id);
        $this->game->theah->eventCheck($event);
        $this->game->theah->queueEvent($event);

        $this->game->globals->set(Game::CHALLENGE_ACCEPTED, false);
        $this->game->gamestate->nextState("cardDiscarded");
    }
}
