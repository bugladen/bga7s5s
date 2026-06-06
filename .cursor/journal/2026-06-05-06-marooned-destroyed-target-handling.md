# Marooned (01148) — destroyed-target handling in 01148_3 / 01148_4

## The bug

In states `highDramaPhase01148_3` and `highDramaPhase01148_4`, the discard loop can leave `CHOSEN_TARGET` pointing at a character that has since been destroyed (e.g., a reaction triggered by the discard event killed it). The card object still exists in `this.cardProperties` (it's a memory of all cards seen), so the `if (card)` check passes — but the DOM element `${card.divId}_image` has already been removed when the character was destroyed, so `$(...)` returns null. `dojo.addClass(null, ...)` then throws.

## Initial wrong direction

I first read the user's "onEnteringState and onLeavingState should detect and handle" as a PHP request and built a `State_highDramaPhase01148_3` `GameState` class with PHP-side hooks. The user clarified: the bug is JS-side in `OnEnteringState.7s5s.js`, and `targetImage` is the null. Reverted all the PHP work — `states.7s5s.php` is back to original, `State_highDramaPhase01148_3.php` deleted.

**Lesson for next time:** "onEnteringState" in this codebase is overloaded. There's a PHP `GameState::onEnteringState` lifecycle hook AND a JS dispatcher object literal in `OnEnteringState.7s5s.js` keyed by state name. When the user mentions a state by its JS name (`highDramaPhase01148_3`, camelCase, no `HIGH_DRAMA_PLAYER_TURN_` prefix), they almost certainly mean the JS side — that's the name format used in `states.7s5s.php` and in the JS dispatchers, not the PHP `States::` constant. Check both before committing to one.

## What I changed

Added a null-guard on `targetImage` in four places:

- `OnEnteringState.7s5s.js` — `highDramaPhase01148_3` (also removed a stray `console.log(targetImage)`)
- `OnEnteringState.7s5s.js` — `highDramaPhase01148_4`
- `OnLeavingState.7s5s.js` — `highDramaPhase01148_3`
- `OnLeavingState.7s5s.js` — `highDramaPhase01148_4`

Each handler now does:

```js
card = this.cardProperties[...targetId];
if (card)
{
    const targetImage = $(`${card.divId}_image`);
    if (targetImage)
    {
        dojo.addClass(targetImage, '_7sfs-chosen');  // or removeClass on leaving
    }
}
```

The `if (card)` check stays — `cardProperties` should normally have the entry but the existing code already treated it as optional. The additional `if (targetImage)` handles the destroyed-DOM case.

## Why this is the right scope

The PHP side is fine. The state machine already routes back through 01148_2 which gates on `target->ControllerId > 0`, so the loop terminates correctly once a destruction propagates. The visual highlight code just needed to tolerate the case where the target's DOM has gone away — a UI nicety, not a state-machine fix.

## Stray console.log removed

Line 1771 had a leftover `console.log(targetImage);` — looks like a debugging trace from when this was first being chased. Dropped it.

## Follow-up: hide Confirm when target dead

User followed up: in 01148_3, if the target is no longer in play, only the Finished button should show — no point letting the player select a card to discard if there's nothing to Engage/Wound afterward.

Went server-side rather than DOM-sniffing. Added `targetInPlay` to the args in `Action_01148::getArgsFromAction` for `HIGH_DRAMA_PLAYER_TURN_01148_3`:

```php
$args["targetInPlay"] = $target !== null && ! $game->characterIsInDiscardOrLocker($target);
```

**Correction worth remembering:** I first reached for `$target->ControllerId != 0` as the "in play" test (mirroring the StatesTrait.php:837 pattern for challenges, where `ControllerId == 0` does indicate destruction in that flow). User pointed out that doesn't fire here — the destroyed character still has `ControllerId` set when this check runs. The reliable signal is location: `characterIsInDiscardOrLocker()` (UtilitiesTrait.php:945) checks for `Discard-` or `Locker-` prefix in `Character->Location`. This is the helper used by all the duel maneuvers (01055, 01059, 01107, 01110, 01164, 01165, etc.) and is the canonical "is this character out of play" test.

Then in `OnUpdateActionButtons.7s5s.js` `highDramaPhase01148_3`, gate the Confirm button on `args.args.targetInPlay`. Finished is always shown.

WHY server-side: the server knows the true state. A JS check via `$(card.divId + '_image')` works but is brittle (depends on DOM update ordering vs state entry). The server already loads the target via `getCharacterById` for other args — adding one boolean is cheap.

Args path: noted `args.args.X` in OnUpdateActionButtons vs `args.args.args.X` in OnEnteringState. The dispatcher in OnUpdateActionButtons receives args pre-unwrapped one level. Matches the existing `args.args.isEngaged` usage at line 681 (01148_4).
