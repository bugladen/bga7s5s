<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04005_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04005_2,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "highDramaPhase04005_2",

            description: clienttranslate('Denounced, Disgraced') . clienttranslate(': Each player must discard a card.'),
            descriptionMyTurn: clienttranslate('Denounced, Disgraced') . clienttranslate(': ${you} must choose a card to discard:'),
            transitions: [
                "multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: "Each player discards a card" — include the acting player (unlike
        // Patricia 01095 opponents-only). Only activate players who have a hand card;
        // empty hands cannot pay the effect. Empty list immediately fires multipleOk
        // (same BGA behavior as dusk discard with no players over Panache).
        $playersToDiscard = [];
        $deck = $this->game->getGameDeckObject();
        foreach ($this->game->loadPlayersBasicInfos() as $playerId => $_)
        {
            $playerId = (int)$playerId;
            $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $playerId);
            if (count($hand) > 0)
            {
                $playersToDiscard[] = $playerId;
            }
        }

        $this->game->gamestate->setPlayersMultiactive($playersToDiscard, "multipleOk");
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

    public function zombie(int $playerId): void
    {
        // WHY: Mirror State_highDramaPhase01095 — clear without forcing a discard.
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }
}
