# Glorious (_03033) Implementation

## Context

Eddie asked to finish Montaigne Virtue Risk **Glorious** — Forced + Gambling Maneuver only (no Action/Reaction).

## Card Text

**Forced:** After your adversary is destroyed, if this card is in your dueling line • Your participant heals a wound.

**Gambling Maneuver:** If your participant has equal or greater [Influence] than the adversary • Wound the adversary.

## Design Decisions

### Forced on Risk class (Pattern E)

Handled directly on `_03033::handleEvent`, mirroring `_01102` (dueling-line gate) + `_02052` (adversary-destroyed duel logic).

Gate chain:
- `EventCharacterDestroyed`
- `$this->Location == LOCATION_DUELING_LINE`
- `IN_DUEL`
- Destroyed character is the adversary of this card's controller (challenger wins when defender destroyed, and vice versa)

Heal participant only if not in discard/locker and `Wounds > 0` — matched `Maneuver_01052` end-of-round heal discipline.

**WHY on card not a separate ability class:** Forced passives on Risks with no player choice belong on the card (`_01102` precedent). No states, no sub-ability.

### Gambling Maneuver (Pattern C, pure resolve)

`Maneuver_03033` — wound-only, no calc branch (pure resolve like `Maneuver_01055`).

Availability:
- `DUEL_GAMBLED` (Gambling gate)
- `ModifiedInfluence >=` adversary (equal or greater — card text; `_03008` uses strict `>` for "more than")
- Adversary not in discard/locker

Resolve: `createCharacterBeingWoundedEvent` with `eventCheck` before queue — same as `Maneuver_03009`.

## Files

- `modules/php/cards/faf/_03033.php` — wired `IHasManeuvers`, Forced handler
- `modules/php/cards/faf/maneuvers/Maneuver_03033.php` — new

Not in StarterDecks (grep found no 03033 entry).

## Feelings

Clean two-ability card — no framework work needed. The adversary-destroyed participant lookup is the same shape as Gutter Full of Roses forced, just scoped to the card owner instead of any player at a location.
