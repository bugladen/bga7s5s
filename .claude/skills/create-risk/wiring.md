> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## State Wiring (`states.inc.php`)

For Pattern A City Actions, add a transition entry under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. Most Risk City Actions that issue challenges use the shared chooser:

```php
"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
```

If your Action transitions to a custom sub-state for a non-challenge effect, add a `States::HIGH_DRAMA_PLAYER_TURN_NNNNN` constant in `States.php` plus a state definition in `states.7s5s.php` (or a GameState class in `States/<expansion>/`). State ID convention: `4` + `CardNumber` zero-padded (e.g., `_03008` → `403008`). Don't engineer around hypothetical CD-card collisions. (Memory feedback.)

For Pattern C Maneuvers that transition to a sub-state (e.g., `Maneuver_01115`), add an entry under the duel's resolve-maneuver transition map and define the state. Mirror `Maneuver_01115`'s wiring.

**Miyato/Ota mirror (`DUEL_CHOOSE_TECHNIQUE_EVENTS`):** when a Maneuver queues a card-specific transition (`createTransitionEvent(..., "NNNNN", …)`) and the Risk is **Neutral or Ussura** (factions covered by the Neutral/Ussura block in `states.inc.php`), also add `"NNNNN" => States::DUEL_RESOLVE_MANEUVER_NNNNN` under `DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions`. WHY: `Technique_02043a` (Miyato and Ota) clones the Maneuver and re-fires Activate→Resolve→Calc while still draining choose-technique EVENTS — without the key, a Resolve-queued chooser throws impossible transition. Pattern C.8 / C.9 (`_03059`, `_03069`) and other state-bearing Neutral/Ussura Maneuvers in that block. Normal combat-card play only needs `DUEL_RESOLVE_MANEUVER_EVENTS`.

For Pattern C.5 "you choose their combat card" hijacks, wire under **`DUEL_GAMBLE_REVEALED_EVENTS.transitions`** (after reveal), not resolve-maneuver. State id convention near the choose family: `5270NNNNN` (see `States::DUEL_CHOOSE_GAMBLE_CARD_03047`).

For Pattern D.5 deck-reveal Reactions that must show chooseList **before** Use/Pass, also wire under **`DUEL_GAMBLE_REVEALED_EVENTS.transitions`** with a distinct key (`"04010"`). State id: `52730NNNNN` (see `States::DUEL_GAMBLE_REVEALED_04010 = 527304010`). Use `createTransitionEvent` (priority 8), **not** `createReactionTransitionEvent` (priority 6 → early `playerReaction` before chooseList). Both Use and Pass must return to `DUEL_GAMBLE_REVEALED_EVENTS` so leftover transitions (C.5 `"03047"`) and `endOfEvents` → choose still run. Public `cards` via `getArgsFromReaction` + `argsForState`. JS: display-only chooseList + Use/Pass (mirror `duelChooseGambleCard_03047` enter/leave). On the Risk class, `addCardToWorld($this)` before `actFromCard*` so deck-card `setUsed` persists.

### GameState class vs legacy array state

Two formats coexist for sub-state definitions:

- **Legacy array** in `states.7s5s.php` (e.g., `States::HIGH_DRAMA_PLAYER_TURN_01059`). Supports `""` as the default unnamed transition; the Action calls `$game->gamestate->nextState()` (no arg).
- **GameState class** in `modules/php/States/<expansion>/State_highDramaPhase<NNNNN>.php` (e.g., `State_highDramaPhase03009`, `State_highDramaPhase03cd01_2`). Uses **named transitions** in the `transitions:` array (e.g., `"locationChosen" => HIGH_DRAMA_PLAYER_TURN_EVENTS`); the Action must call `$game->gamestate->nextState("locationChosen")` to match the named key. Don't use `""` as a transition key on GameState classes.

For new card work, prefer the GameState class format. Model after `State_highDramaPhase03009` for a single-step location-chooser, or `State_highDramaPhase03cd01_2` for one with an `actBack` to a previous sub-state.

```php
class State_highDramaPhase<NNNNN> extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_<NNNNN>,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase<NNNNN>",
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('<Card Name>') . clienttranslate(': ${you} must choose ...:'),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void { $this->game->actFromCardWithLocations($locations); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState("zombie"); }
}
```

`actFromCardWithLocations` (string-encoded JSON array of location names) dispatches into the framework, which calls your Action's `actFromActionWithIds(Game $game, int $state, string $stateName, array $ids)` with `$ids[0]` being the chosen location string.

## JS State Hooks

When you add a card-specific sub-state, you usually need three matching JS handlers. For the **`modules/js/On*.faf.js`** files (mirrored for `_7s5s` and `tac`):

- **`OnEnteringState.faf.js`** — under the `methods` map, add `'highDramaPhase<NNNNN>': () => { ... }`. Highlight the performer, make valid targets selectable, stash chosen ids into `this.clientStateArgs` for cleanup.
- **`OnUpdateActionButtons.faf.js`** — add a confirm button. For location chooser: `this.addActionButton('actCityLocationsSelected', _('Confirm Location'), () => this.onCityLocationsSelected());` + `dojo.addClass('actCityLocationsSelected', 'disabled');`. For card chooser: `actChooseCardSelected` + `onChooseInPlayCardConfirmed`.
- **`OnLeavingState.faf.js`** — undo highlights / `resetCityLocations()` / clear `this.clientStateArgs`.

Pattern reference for the trio: `highDramaPhase03cd01_2` (Penya — location chooser with both performer and target highlight) and `highDramaPhase03009` (single-performer + location-chooser).

**Multi-player hand discard** (Pattern B.5 / Denounced `_04005_2` / Patricia `_01095`): GameState is `MULTIPLE_ACTIVE_PLAYER`. Entering: `setPlayersMultiactive($playerIds, "multipleOk")` — **all** discarders are active concurrently (BGA does **not** pass a single turn around). Leaving each player: `setPlayerNonMultiactive($playerId, 'multipleOk')`. JS: `factionHand.setSelectionMode('single')` + `actChooseDiscardCard` / `onCardDiscarded` + `EventHandlers.js` enable when selection length &gt; 0. Mirror `highDramaPhase04005_2` / `highDramaPhase04018_2`.

**Skip the JS trio** when the chooser lives entirely in `playerReaction` button properties (Pattern D.1 / D.1.1 — e.g. Confusion `_03068` character then city-location buttons). Those need no GameState and no On*.js handlers; inventing Action-style board-highlight scaffolding is a regression trap.

## Pre-Commit Hook Compliance

The `.githooks/pre-commit` hook checks staged PHP files. Risk-related rules:

| Class shape | Required literal strings |
|---|---|
| `extends RiskCityAction` | `createActionResolvedEvent` somewhere in the file (the comment `// createActionResolvedEvent() is called when the challenge is resolved` satisfies the hook for challenge-issuing actions where resolution fires it elsewhere). |
| `extends RiskAction` | Same as RiskCityAction. |
| `extends Maneuver` | An `EventManeuverCanceled` handler OR the comment `// EventManeuverCanceled handler not needed`. |
| `extends RiskReaction` | `Location == Game::LOCATION_HAND` check, plus `$this->setUsed(` and `$this->isAvailable(` literal calls. |
| `extends CardReaction` / `AttachmentReaction` | `$this->setUsed(` and `$this->isAvailable(` — **no** hand guard (Pattern D.5 deck-reveal uses this). |
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent()` AND `createSorcererAbilityPlayedEvent()` literal calls. |
| Mixing `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on the **same** class | **Forbidden** — split into separate ability classes if the card text demands both. |

A Risk card that both extends `Risk` AND has Actions/Maneuvers/Reactions in separate files means the hook runs per-file: the Risk class itself has no Action/Reaction lint, but each ability file is checked independently.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Risk class:   `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:       `...\cards\<expansion>\actions`
  - Reaction:     `...\cards\<expansion>\reactions`
  - Maneuver:     `...\cards\<expansion>\maneuvers`
- **State ID convention:** `4<NNNNN>` for High-Drama player-turn states owned by a card. (Memory feedback.)
- **"Opposing"** means BOTH different controller AND same location.
- **Modified stats** (`ModifiedInfluence`, `ModifiedFinesse`, …) — use these for live comparisons, not the printed base values.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.
- **Typed PHP parameters required.** Every function/method signature must declare a type for every parameter — no bare `$foo`. Use concrete types (`Card $owner`, `Character $performer`, `Game $game`, `Theah $theah`, `Event $event`, `int $cardId`, `string $reactionId`). Add the `use` import.
- **"Strega" / "Mercenary" / "Diplomat" / "Duelist" / etc.** are **mechanical performer-trait gates**, not flavor. Enforce via `hasTrait("Strega")` on the performer / `getDuelRoundActor()`. They are NOT Sorcerer abilities — do NOT `implement ISorcererAbility` for them. Only the literal "Sorcerer" keyword triggers `ISorcererAbility`. They can stack.
- **`IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters`** — mark when printed text says **"Target"/"target"** (Rules Team + Cesca `Reaction_01008`). The interface is not merely "has a character chooser UI" — `_03060` heals "another character" without "target", `_03069` swaps with "your other character" without "target", and both must **not** implement either (and must not be on Cesca's copy whitelist). Compare `_01083`, `_01115`, `_03008`, `_03011`, `_03034`. Skip for location-only / hand-discard / fixed-trigger choosers.

