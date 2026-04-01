# Tending the Wounded (_01175) Audit

## Card Text
> Action: Discard any number of cards • Target non-Leader character you control heals a wound for each card discarded this way.

Risk card, WealthCost=0, Riposte=0 (dashed), Parry=3, Thrust=2, Faith and Penance traits. Single action (`Action_01175`), performer selection required, then card discard via state `01175`.

## Result: No bugs found

This card is cleanly implemented. All code matches the card text.

## Verified Correct

- **Action availability** (`isAvailableToPlayer`): Checks (1) player has at least one non-Leader character with wounds, and (2) player has at least one card in hand. Both required for the action to make sense.
- **Performer list** (`getPerformersForAction`): Returns non-Leader characters controlled by the player with `Wounds > 0`. Uses `getCharactersInPlayByPlayerId` which includes characters at city locations AND player home — correct since the card text has no location restriction ("character you control").
- **Target validation** (`isValidTargetForAbility`): Checks non-Leader and wounded. Consistent with `getPerformersForAction` filter.
- **Card discard validation** (`actFromActionWithIds`): Each card validated for ownership (`OwnerId == ControllerId`) and location (`LOCATION_HAND`).
- **Wound cap**: `if ($performer->Wounds < $wounds)` prevents discarding more cards than the character has wounds. Prevents misplays — you can't "waste" cards healing beyond 0 wounds.
- **Heal amount**: `$wounds = count($ids)` — one wound healed per card discarded. Matches "heals a wound for each card discarded this way."
- **Event ordering**: Discard events queued first, then heal event. Correct — cards are discarded before healing resolves.
- **Post-action cleanup**: `resetPlayerPassCount` and `createActionResolvedEvent` both called. Standard pattern.
- **JS client**: `factionHand.setSelectionMode('multiple')` allows multi-select. Confirm button enables/disables based on selection count. `onCardsDiscarded()` gathers IDs and calls `actFromCardWithIds`.
- **Constructor** (`_01175.php`): WealthCost=0, Riposte=0 (DashedRiposte=true), Parry=3, Thrust=2, Faith/Penance traits. All correct.

## Minor Notes (not bugs)

- `getPerformersForAction` has a redundant `array_values` — `array_filter` result is already wrapped in `array_values`, then the return wraps in `array_values` again. Harmless.
- Server-side performer validation gap: `actHighDramaInPlayActionPerformerChosen` doesn't re-validate the performer against `getPerformersForAction`. This is a framework-level pattern, not specific to this card. Client-side filtering prevents invalid selections in normal play.
