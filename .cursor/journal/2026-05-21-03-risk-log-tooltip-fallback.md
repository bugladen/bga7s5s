# Risk Log Tooltip Falling Back to "Name (Type)"

## Bug Report

When an opponent plays a Risk and the viewing player has the text tooltip preference (USER_PREFERENCES_CARD_HOVER_TYPE = 2) selected, hovering the card name in the game log shows only `${CardName} (Risk)` instead of the full stats/text tooltip.

## Root Cause

The text-tooltip-log machinery (see `2026-03-23-05-text-tooltip-preference.md`) relies on `format_string_recursive_with_injection` in `seventhseacityoffivesails.js:175-187` scanning notification args for objects that have both `id` and `type` and stashing them in `this.logCardCache`. That cache feeds `addTooltipsToLog`, which builds the rich tooltip.

For the player playing the Risk, their own hand cards are already in `this.cardProperties`, so the tooltip works. For the OPPONENT, that card was never in `cardProperties` (the hand is private), so the only chance to grab the card data is the `card` arg on the notification.

The two Risk-play notifications in `FrameworkActionsTrait.php` were sending only `card_inject_code` (a string like `[01076:Risk:Burden of Atlas(image.png)]`) and `player_name`. No structured `card` object → cache miss → fallback to the minimal `Name (Type)` tooltip in `addTooltipsToLog`.

- `FrameworkActionsTrait.php:971` — `actPayForInHandAction` (main Risk play)
- `FrameworkActionsTrait.php:2004` — `actPayForReaction` (Risk reaction payment / "play Risk to react")

## Fix

Added `"card" => $risk->getPropertyArray($this)` (and the `$card` equivalent in the reaction path) to both notifications. The JS scanner now finds the card object, populates `logCardCache`, and `addTooltipsToLog` renders the full text tooltip.

## WHY this approach over alternatives

- **Sending the data in the inject code itself**: bloats every log entry with dozens of properties — rejected previously in the 03-23 journal.
- **Stashing into `cardProperties` on Risk play**: would happen client-side in the `notif_*` handler, but BGA runs notification handlers AFTER log formatting (see 03-25 timing bug in the same journal). So even handler-side caching wouldn't help log tooltips. The pre-cache scan in `format_string_recursive_with_injection` is the correct hook.
- **Just passing the inject code**: that's what was already happening and it doesn't work for opponents.

So: include the full property array as a `card` arg. This is the same convention used everywhere else (ArgumentsTrait, EventHub, Reaction_01008, Technique_01090, etc.) — Risk play notifications were just inconsistent with that pattern.

## Files Modified

- `modules/php/FrameworkActionsTrait.php`: added `"card" => $risk->getPropertyArray($this)` to the two Risk-play `notify->all` calls.

## Verification (untested)

I have not run this in-game; the change is mechanical and matches the convention used in dozens of other notifications. Worth eyeballing in a live test by playing a Risk while the opponent has text tooltips enabled.
