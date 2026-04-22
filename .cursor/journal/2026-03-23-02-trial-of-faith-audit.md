# Trial of Faith (_02019) Audit

## What I found

One bug: the `EventPressureOccuring` handler in `Reaction_02019.php` didn't check whether the pressure included Influence (`[inf]`). The card text specifically says "pressured with [inf]" but the code triggered on any pressure type — Combat, Finesse, Resolve, all of them. This means the reaction would incorrectly offer to wound a character and add the wound-count bonus even on non-Influence pressures.

Fixed by adding `in_array(Game::STAT_INFLUENCE, $event->pressureTypes)` to the condition alongside the existing `$owner->Location == Game::LOCATION_HAND` check.

## WHY other things are correct

The Zealot heal is automatic rather than optional ("may"). I considered flagging this as a bug but decided it's a reasonable simplification. The heal fires on `EventLocationPressureResult`, which is AFTER `pressureLocation()` has already calculated totals and determined the winner. So the wound has already been counted for the +1 bonus. The only reason to skip the heal would be if another card downstream benefits from wounds — a niche edge case. If Eddie wants it to be truly optional, it would need a second reaction transition event after the pressure result, which is doable but adds complexity.

The wound timing is correct: `EventRiskReactionTriggered` queues the wound event, which gets processed before the game state transitions to `stHighDramaPressureLocation` where `pressureLocation()` runs. So `$character->Wounds` already includes the new wound when the +1-per-wound bonus is calculated.

## Pattern observation

Pressure type checking is inconsistent across reaction cards. `Reaction_01184` (Claude) and `Reaction_02004` (Crash the Party) both listen to `EventPressureOccuring` without filtering on pressure type — but their card texts don't require a specific type. Trial of Faith was the one that needed the filter and didn't have it. Worth keeping in mind for future audits of pressure-related reactions.
