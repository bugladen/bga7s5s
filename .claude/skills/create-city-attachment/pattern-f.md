> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern F — Custom State Inserted Into the Core Flow

If the attachment needs to prompt the equipped character's controller *during* an existing core state (mid-duel, mid-pressure, etc.), and that core state is a `game`-type (auto-running) state, you cannot stuff a player choice into it directly. The framework forbids interactive logic in game states.

**`_03cd05` pattern — splitting setup from execution:**

1. Add a new `<thing>Setup` game state *before* the auto state.
2. Add an immediately-following `<thing>SetupEvents` state that runs queued events (transitions, reactions, pay-for-reaction).
3. Reroute *all* existing transitions into the original auto state to point at the new setup state instead.
4. Have the original auto state run as before; the new setup states provide the choice window.

For `_03cd05` this looked like:

```
* → DUEL_GAMBLE_SETUP (game, stDuelGambleSetup: queues EventGambleSetup)
  → DUEL_GAMBLE_SETUP_EVENTS (game, stRunEvents)
       ↳ "03cd05" → DUEL_GAMBLE_SETUP_03CD05 (activeplayer: top/bottom choice)
       ↳ "reaction" → DUEL_GAMBLE_SETUP_REACTIONS
       ↳ "pay"      → DUEL_GAMBLE_SETUP_PAY_FOR_REACTION
       ↳ "endOfEvents" → DUEL_GAMBLE_REVEALED  ← original entry point
```

Four prior transitions to `DUEL_GAMBLE_REVEALED` were rerouted to `DUEL_GAMBLE_SETUP`:
- `DUEL_CHOOSE_ACTION.chooseGambleCard`
- `DUEL_COMBAT_CARD_EVENTS["01135"]`
- `DUEL_SET_NEXT_COMBAT_CARD.rollTheBones`
- `DUEL_CHOOSE_GAMBLE_CARD_EVENTS["01135"]`

**Mint a new `EventXxxSetup` event** that the attachment listens for. Carries `actorId` (the participating character) and `playerId` (their controller). Follow the shape of `EventDuelAttemptGamble` — minimal fields, no-op handler in `EventHub`, registered in `Events::XxxSetup`, factory in `EventFactory::createXxxSetupEvent`.

**Mint matching globals where needed** for per-trigger choices (e.g. `Game::GAMBLE_REVEAL_FROM_BOTTOM`). Clear them in the matching cleanup state (`stDuelEndOfRound` for duel-scoped flags).

**Defensively reset on the default branch.** If `id == 1` (default/top), explicitly set the global to `false` — don't just "leave it alone." A previous round may have left it `true`, and you want each new gamble to start clean.

### Player-choice state class

State files live in `modules/php/States/<expansion>/` (per the Penya/Chance Meeting pattern). Example for `_03cd05`:

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelGambleSetup_03cdNN extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::DUEL_GAMBLE_SETUP_03CDNN,
            type: StateType::ACTIVE_PLAYER,
            name: "duelGambleSetup_03cdNN",
            description: clienttranslate('${actplayer} is choosing options from <card name>.'),
            descriptionMyTurn: clienttranslate('Card Name') . clienttranslate(': ${you} may ...'),
            transitions: [
                "" => States::DUEL_GAMBLE_SETUP_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void { $this->game->actFromCardWithId($id); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState(); }
}
```

The card class's `actFromCardWithId` interprets the `$id` (1 = default, 2+ = chosen options) and writes the global. Zombie players fall through to `nextState()` without setting the global — defaults to the safe-baseline behavior.

### State ID convention

Follow the faf pattern. New duel-flow states use a duel-scoped prefix; new high-drama-action states use `403XXXX` for expansion 3 (4 = high drama, 03 = expansion, XX = card number). `_03cd05`'s setup state constant is `States::DUEL_GAMBLE_SETUP_03CD05`.

### JS wiring is required

Without JS, the new state activates server-side but the player sees nothing. For an attachment that adds a duel-setup prompt:
- `modules/js/OnUpdateActionButtons.<expansion>.js` — render the choice buttons (e.g. "Reveal from Top" / "Reveal from Bottom").
- Ensure the expansion's JS file is included from the master `OnUpdateActionButtons.js` chain.

For action-flow states (high-drama actions), additionally wire:
- `OnEnteringState.<expansion>.js` — selection setup.
- `OnLeavingState.<expansion>.js` — selection teardown.
- `PlayerActions.js` — extend the client-side action map if you reuse an existing client action like `onMusterCardSelected`.
