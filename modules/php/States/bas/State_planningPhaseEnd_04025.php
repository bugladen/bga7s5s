<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04025;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseEnd_04025 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_END_04025,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseEnd_04025",

            description: clienttranslate('No Rest for the Wicked') . clienttranslate(': ${actplayer} is looking at the top of their deck.'),
            descriptionMyTurn: clienttranslate('No Rest for the Wicked') . clienttranslate(': ${you} must choose two cards to draw (the rest will be sunk): '),
            transitions: [
                "" => States::PLANNING_PHASE_END_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Looked-at faction deck cards must stay private (Yevgeni 03052 / Penya).
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        // Auto-draw the first cardsToDraw looked-at cards.
        // WHY: lookedCards rebuilds from the deck when chosenCard still holds Embargo's card id.
        $sourceId = (int) $this->game->globals->get(Game::TRANSITION_SOURCE_ID);
        $card = $this->game->theah->getCardById($sourceId);
        $internalId = (string) $this->game->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $reaction = $card?->getReactionById($internalId);
        $cards = $reaction instanceof Reaction_04025
            ? $reaction->lookedCards($this->game)
            : Reaction_04025::decodeCardList($this->game->globals->get(Reaction_04025::LOOKED_CARDS));
        $drawCount = min(2, count($cards));
        $ids = [];
        for ($i = 0; $i < $drawCount; $i++)
        {
            $ids[] = (int) $cards[$i]->id;
        }
        $this->game->actFromCardWithIds(json_encode($ids));
    }
}
