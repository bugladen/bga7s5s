# Ved'ma (01124) — Pass button when chooseList is empty

## Problem

`Action_01124::isAvailableToPlayer` confirms there's at least one affordable Sorcery in the discard pile at trigger time. But between trigger and state entry, queued events resolve — and a Boon-triggered Reaction can mutate the discard pile (e.g. yank or convert the candidate Sorcery) before `getArgsFromAction` runs. The state then renders zero action buttons and the player is softlocked at `highDramaPhase01124` — can't choose, can't exit.

User flagged this directly: "if there are no risks in chooseList, add a pass button."

## Change

Three files, mirroring the Robbery (`Action_01113`) pattern that already does this when its opponent list collapses:

1. `State_highDramaPhase01124.php` — added `"pass" => HIGH_DRAMA_PLAYER_TURN_EVENTS` transition and an `actFromCardPass()` `#[PossibleAction]` that delegates to `$this->game->actFromCardPass()`. Routing then flows through `FrameworkActionsTrait::actFromCardPass` → `Card::actFromCardPass` → `Action::actFromActionPass`.
2. `Action_01124.php` — added `actFromActionPass(Game $game, int $state): void` which queues `createActionResolvedEvent` and calls `nextState("pass")` when state matches `HIGH_DRAMA_PLAYER_TURN_01124`.
3. `OnUpdateActionButtons.7s5s.js` — in the `highDramaPhase01124` handler, when `args.args.actions.length === 0` add an alert-colored "Pass" status-bar button calling `actFromCardPass`.

## WHY: no Sorcerer-ability events on pass

The pass branch deliberately does NOT queue `createSorcererAbilityStartEvent` or `createSorcererAbilityPlayedEvent`. The user explicitly chose "Neither" when asked. Rationale: no Sorcery actually resolved, so emitting the lifecycle events would be misleading to listeners — and to the game log. The pre-commit `ISorcererAbility` invariant ("must call Start AND Played") is still satisfied because the choose-action branch in the same file still references both helpers; the hook is a static grep, not a path-coverage check.

If a future listener pairs Start/Played and assumes they always both fire, this could desync. Watching for that.

## WHY: Ved'ma stays engaged on pass

The engagement was relocated from `actFromActionWithActionId` into `handleEvent` (see staged diff and the `2026-05-12-02-vedma-01124-duplicate-discard-removal.md` predecessor work). That means by the time the player sees the choose list, Ved'ma is already engaged — engagement is the paid cost of triggering the ability, not the cost of resolving it. Passing because the targets vanished is the player's loss, matching MTG-style cost-was-paid semantics. Did not add an "un-engage on pass" branch — would have been a separate design call and not what the user asked for.

## Verification

Not yet tested end-to-end on Studio. To repro: queue a Boon reaction that strips the lone Sorcery from the discard pile during event resolution between Ved'ma's trigger and state entry. The chooseList renders empty, and the new Pass button should appear in the status bar. Click → `HIGH_DRAMA_PLAYER_TURN_EVENTS`, Ved'ma still engaged, no errors.

## Loose threads

- Should we also notify "Ved'ma's action fizzled" in the game log on pass? Right now `createActionResolvedEvent` is queued but there's no specific message that the Sorcery target disappeared. Leaving alone unless requested — the empty chooseList plus active Pass button should be self-evident to the player.
- The `cards`/`actions` args in `getArgsFromAction` filter is a redundant double-pass (filter `Sorcery` on cards, then iterate to collect `availableActions`). Not touching — out of scope for this fix.

## How to grant a player extra actions (reference)

User asked to give the player an extra turn on the Ved'ma pass branch. Recording the mechanism since it's not obvious from the code shape.

The global `Game::EXTRA_ACTIONS` (string key `"extraActions"`, defined `Game.php:74`) drives this. The consumer is `StatesTrait::stNextPlayer()` (around `StatesTrait.php:1710`):

```php
$extraActions = $this->globals->get(Game::EXTRA_ACTIONS, 0);
if ($extraActions > 0) {
    $this->globals->set(Game::EXTRA_ACTIONS, $extraActions - 1);
    // notify "X EXTRA ACTION(S) LEFT"
} else {
    $nextPlayerId = $this->getPlayerAfter($currentPlayerId);
    $this->globals->set(Game::CURRENT_PLAYER, $nextPlayerId);
}
```

So to grant N extra actions, set the global before the `ActionResolved` event resolves and the state transitions through `NEXT_PLAYER`/`stNextPlayer`. Each `stNextPlayer` invocation decrements by 1 and re-activates the same player until it hits 0.

**Usage pattern in the codebase** (all 3 existing examples just SET, never increment):
- `Action_01090.php:52` — `$game->globals->set(Game::EXTRA_ACTIONS, 1);` (Reaction grants 1 extra action)
- `Action_01139.php:50` — `$game->globals->set(Game::EXTRA_ACTIONS, 2);` (grants 2)
- `Action_01154.php:253` — `$game->globals->set(Game::EXTRA_ACTIONS, 1);` (Corpse Speak softlock-escape: identical scenario to Ved'ma pass — engaged character has no usable action, en garde + grant 1 extra)

**WHY SET vs incremental add**: All existing callers use `set()` not `set(get() + 1)`. This clobbers any pre-existing extra actions. None of the current callers compose so it hasn't mattered. If a future card needs to stack extras, switch to read-then-add. For Ved'ma's pass branch I mirrored the existing SET=1 pattern to stay consistent with Corpse Speak.

**WHY this works with `ActionResolved`**: `EventActionResolved` has `Event::CHANGE_ACTIVE_PLAYER_PRIORITY = 8` and ends up routing through `NEXT_PLAYER → stNextPlayer`. The `EXTRA_ACTIONS` global is read inside `stNextPlayer` BEFORE the player-advance branch, so setting it any time before `ActionResolved` resolves is fine. In my Ved'ma fix I set it in the same `actFromActionPass` method that queues `ActionResolved` — simplest.

**Cleared by**: `stNextPlayer` decrements toward 0 naturally. There is no explicit "reset on phase end" — once the counter is exhausted, it stays at 0 until something else sets it. Verify if any phase-end code clears it if you need stricter scoping (skipped that check; not relevant here).

**Notification**: The "${player_name} NOW HAS ${extra_actions} EXTRA ACTION(S) LEFT" message is emitted from `stNextPlayer` automatically when consuming an extra action. You don't need to notify on the GRANT side, but Corpse Speak (and now Ved'ma) does send its own "...the player gains an extra action" message at grant time as context for why the same player is going again.
