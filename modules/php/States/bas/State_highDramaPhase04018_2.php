<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04018;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04018_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04018_2,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "highDramaPhase04018_2",

            description: clienttranslate('Seek Each Devil') . clienttranslate(': Players who control a Sorcerer or Monster there must discard a card.'),
            descriptionMyTurn: clienttranslate('Seek Each Devil') . clienttranslate(': ${you} must choose a card to discard:'),
            transitions: [
                "multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Activate only players stashed on Action_04018 (other controllers of
        // Sorcerer/Monster at destination with a hand card). Empty list immediately
        // fires multipleOk (same BGA behavior as dusk discard / 04005_2).
        $this->game->theah->buildCity();

        $playersToDiscard = [];
        $sourceId = $this->game->globals->get(Game::TRANSITION_SOURCE_ID);
        $internalId = $this->game->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $card = $this->game->theah->getCardById($sourceId);
        if ($card !== null)
        {
            $ability = $card->getAbilityById($internalId);
            if ($ability instanceof Action_04018)
            {
                $deck = $this->game->getGameDeckObject();
                foreach ($ability->PlayersToDiscard as $playerId)
                {
                    $playerId = (int)$playerId;
                    $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $playerId);
                    if (count($hand) > 0)
                    {
                        $playersToDiscard[] = $playerId;
                    }
                }
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
        // WHY: Mirror State_highDramaPhase04005_2 — clear without forcing a discard.
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }
}
