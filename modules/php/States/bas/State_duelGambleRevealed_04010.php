<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

/**
 * WHY: Unravel the Thread — show revealed gamble cards in chooseList, then Use/Pass.
 * Runs after Ivy-style pre-choose reactions (transition priority 8 > reaction 6).
 */
class State_duelGambleRevealed_04010 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_GAMBLE_REVEALED_04010,
            type: StateType::ACTIVE_PLAYER,
            name: "duelGambleRevealed_04010",

            description: clienttranslate('${actplayer} is choosing Reaction options.'),
            descriptionMyTurn: clienttranslate('Unravel the Thread') . clienttranslate(': ${you} may reveal additional cards equal to your performer\'s [Influence] and give your Sorceries +1[Parry] this round:'),
            transitions: [
                // WHY: Both Use and Pass return to REVEALED_EVENTS so leftover transitions
                // (e.g. Proper Drama 03047) and then endOfEvents → choose still run.
                // Use also needs that path for the additional-cards reveal event.
                "use" => States::DUEL_GAMBLE_REVEALED_EVENTS,
                "pass" => States::DUEL_GAMBLE_REVEALED_EVENTS,
                "zombie" => States::DUEL_GAMBLE_REVEALED_EVENTS,
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
        $this->game->actFromCardWithId((int) $id);
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
