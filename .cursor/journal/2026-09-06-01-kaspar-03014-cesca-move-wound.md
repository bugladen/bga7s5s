# Kaspar 03014 vs Cesca move-wound — illegal target + vanishing wound

## Bug
Cesca Leader City Action (`Action_03001`) moved a wound from Bonora (Strega) to Approach Kaspar (`_03014`). Wound left Bonora, never landed on Kaspar. User: Kaspar must not even be choosable.

## Why it happened
Kaspar's passive zeros `$event->wounds` in `eventCheck` on `EventCharacterBeingWounded` when the source is an opponent ability. That correctly blocks the *wound half*.

Cesca's move-wound recipe is heal-then-wound (`createCharacterBeingHealedEvent` then `createCharacterBeingWoundedEvent`). Heal queues first and lands. Wound then gets zeroed. Net: wound deleted.

Original Kaspar journal (`2026-05-25-02`) claimed "move wounds to Kaspar comes free with the wound-block — don't add a special handler." That was wrong for heal+wound ordering and for targeting UX.

## Fix
1. `Character::canBeWoundedByOpponentAbilities()` default `true` — same shape as `canIntervene` / `canChallenge`.
2. `_03014` overrides to `false`.
3. Cesca `Action_03001` filters targets + `isValidTargetForAbility` on that predicate (availability uses the same list).
4. Cesca `Reaction_03001` same filter on wound buttons / queued-move target expansion (cannot wound Kaspar either).

Kept the `eventCheck` zeroing as the runtime safety net for non-filtered wound emitters (duel techniques etc. that auto-target adversary).

## Why not throw in eventCheck instead of zeroing
`Theah::queueEvent` swallows exceptions from `eventCheck` (notifies + skips queue). Throwing on the wound event would still leave a already-queued heal. Targeting rejection is the correct fix for move-wound; zeroing stays for untargeted / auto wound paths.

## Unfinished / follow-up
Other opponent wound-pickers across the codebase do not yet filter `canBeWoundedByOpponentAbilities()`. Those still get eventCheck zeroing (wound fizzles, no heal side effect). Worth a sweep later if more illegal-target complaints show up. Pattern-a.md updated so future wound-prevention cards get the predicate.
