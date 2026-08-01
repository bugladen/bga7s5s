# Reaction_01008 — Exclude uncopyable abilities from trigger

## Problem

Cesca's copy reaction (`Reaction_01008`) was offering the Copy window for Fate's Burden (`Action_01025`) and Boon (`Action_01161`). Both cards explicitly say "This ability cannot be copied."

`performReaction` already had no copy branches for them (just comments "cannot be copied"), but `isCopyable()` still listed them in the allow-list. Result: reaction triggers → player clicks Copy → Cesca takes 1 wound cost → nothing is actually copied. Bad.

## Fix

Removed `Action_01025` and `Action_01161` from `isCopyable()`, and dropped the now-unused `use` imports. The `isCopyable` gate in `handleEvent` (both `EventSorcererAbilityPlayed` and `EventCharacterTargeted` paths) already prevents the reaction window from opening when `isCopyable` returns false.

## WHY gate at isCopyable (not only in performReaction)

The wound cost is paid at the start of the `copyAbility` branch, before any instanceof copy logic. So even with empty copy branches, clicking Copy still wounds Cesca. Blocking at the trigger (`isCopyable`) is the correct place — never offer a choice that can't succeed.

## Note for future

`isCopyable` comment already says keep it in sync with `performReaction` instanceof branches. The uncopyable exclusions are the intentional exceptions to that sync — documented with a WHY comment in `isCopyable`.
