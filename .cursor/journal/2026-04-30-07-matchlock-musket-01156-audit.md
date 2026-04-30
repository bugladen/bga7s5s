# Card _01156 (Matchlock Musket) Audit

Card text: "**City Action:** Discard a card • Target character at an adjacent City location may engage. If they do not, wound them. (They cannot engage if they are already engaged.)"

## Audit results

`Action_01156` (AttachmentAction implementing IAbilityThatTargetsCharacters, IRangedAbility) implements the card text correctly in three states:

- State 01156: active player picks one of their hand cards to discard. Card moves to PURGATORY (a holding location), CHOSEN_CARD set in globals, transition to 01156_2.
- State 01156_2: active player picks a target opposing character at an adjacent city location. If target is already Engaged, immediately discard + wound + announce + resolve action. Otherwise, queue the discard event, save CHOSEN_TARGET, transition to 01156_3 with the target's controller as active player.
- State 01156_3: target's controller chooses Engage (id=1) or Wound (id=2). Closes out with RangedAbilityPlayed + ActionResolved.

Closest reference is `Action_01049` (Polished Flintlock — same engage-or-wound branch). The `Action_01069` shares the PURGATORY-then-discard-event pattern for "discard a card as a cost across multiple states."

## Bugs found and fixed

### Bug 1: Deprecated `\BgaUserException`

Lines 135 and 140 used `\BgaUserException`. The file already imports `Bga\GameFramework\UserException`. Replaced both. Per memory: `\BgaUserException` is deprecated framework-wide.

### Bug 2: Zombie handler crashes on 01156_2 / 01156_3

`ZombieTrait` was routing all three musket states (01156, 01156_2, 01156_3) through `actBack()`. But neither 01156_2 nor 01156_3 has a `"back"` transition defined in `states.7s5s.php` — only `""`. Calling `actBack()` (which is just `nextState("back")`) would throw `BGA_Exception_BadTransition`.

Fix: Moved 01156_2 and 01156_3 into the `nextState()` zombie block alongside `01049_2` (the analogous Polished Flintlock state). Zombies now fizzle the engage/wound choice safely. Left 01156 with `actBack()` because state 01156 does have a `"back"` transition.

WHY this matters: zombie behavior in engage-or-wound flows in 7s5s is "fizzle quietly" — the action resolves with no effect on the engage/wound side. _01049_2 already had this pattern; _01156 was inconsistent.

### Bug 3: Dead `actBack` in possibleactions

Both 01156_2 and 01156_3 listed `actBack` in `possibleactions` despite having no `"back"` transition. The JS UI doesn't bind a back button for these states (verified in `OnUpdateActionButtons.7s5s.js:739–746`), so it's unreachable from the UI, but the zombie path was hitting it (Bug 2 above). Removed both. Pattern now matches _01049_2's possibleactions.

### Bug 4: `$charactersIds` undefined on the no-target path

`getArgsFromAction` for state 01156_2 did `$charactersIds[]` inside a foreach without prior init, then called `array_unique($charactersIds)` unconditionally. If for some reason the entry-into-state happened with no opposing characters at adjacent locations (e.g., a board state change between `isAvailableToPlayer` and state entry, or a copied action via Reaction_02011 bypassing the availability check), this would emit a PHP warning.

Fix: Init `$charactersIds = [];` before the loop. Defensive but cheap.

## Decisions / non-bugs noted

- **PURGATORY-then-discard-event pattern is intentional.** State 1 moves the chosen card to PURGATORY and stores its ID in globals. State 2 then queues `createCardDiscardedFromHandEvent` whose handler moves the card from its current location to the player's discard pile. The PURGATORY hop locks the card in (player has committed to discarding) while the next state collects the target. This matches `Action_01069` (Maxime de Médée's discard-then-recover-attachment). I considered moving the discard event into state 1 directly, but that would fire the discard event before the target is selected, which would let trigger reactions that reference "the discard" land before the rest of the action resolves. The current ordering (target chosen → discard event queued → wound/engage events queued in same flush) is intentional.

- **Already-engaged path skips state 3.** When the chosen target is already Engaged, the code immediately queues discard+wound and resolves the action without going through the engage-or-wound choice. This matches the parenthetical "(They cannot engage if they are already engaged.)". Correct.

- **`isAvailableToPlayer` guards adjacency.** Requires at least one opposing character at an adjacent (non-Home) city location. `isValidTargetForAbility` re-validates at action time. Both correct.

- **The action only fires from the city.** `isAvailableToPlayer` calls `$theah->cardInCity($performer)`. Since the card is `extends AttachmentAction` (no city-specific subclass exists in this codebase — see _01049 for the same pattern), the in-city check is the gate that enforces "City Action."

- **Reaction_02011 (Katain) copies Action_01156.** Verified the copy mechanic just `new Action_01156()` and `setOwnerId` — my changes carry over cleanly. No special handling needed.

## Notes for next session

- The PURGATORY-zombie hole (player goes zombie at 01156_2 *after* discarding to PURGATORY but *before* the discard event fires) is unfixed and shared with `Action_01069`. The card sits in PURGATORY forever in that scenario. Out of scope for a per-card audit; would need a framework-level cleanup pass on PURGATORY orphans, or to push the discard event into state 1 (which has reaction-ordering implications I'd want to think through harder before making).
- `"01156_2"` is correctly absent from the `states.inc.php` transition map — that map is only for `EventFactory::createTransitionEvent` external transitions, and 01156→01156_2 is an internal `nextState("cardChosen")` step.
