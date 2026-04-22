# Ved'ma (01124) - array_filter JSON encoding bug

## The Bug

`args.args.args.cards.forEach is not a function` at OnEnteringState.7s5s.js:1588

## Root Cause

In `Action_01124::getArgsFromAction`, the `$cards` array goes through `array_filter` to keep only Sorcery-traited cards. PHP's `array_filter` preserves original keys - so if the original array was `[0 => A, 1 => B, 2 => C]` and only indices 0 and 2 pass, the result is `[0 => A, 2 => C]`. When `json_encode` sees non-sequential integer keys, it encodes as a JSON **object** `{"0": ..., "2": ...}` instead of an array `[..., ...]`. JavaScript objects don't have `.forEach`.

## Fix

Wrapped `array_filter` in `array_values()` to re-index the keys sequentially.

## WHY this is a common PHP/JS pitfall

This only surfaces when `array_filter` actually removes an element that isn't at the end. If the filtered-out card happens to be the last one, keys stay sequential and it works fine. Classic intermittent bug - depends on which cards are in the discard pile.

## Pattern to watch for

Any PHP code that runs `array_filter` on an array that gets JSON-encoded and consumed as a JS array needs `array_values()`. Worth grepping for similar patterns in other card args methods.
