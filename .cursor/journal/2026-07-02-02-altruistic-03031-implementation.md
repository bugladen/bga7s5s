# Altruistic (_03031) Implementation

## Context

Eddie asked to finish the Montaigne Virtue Risk **Altruistic** — Reaction only card (no Maneuver/Action). Pointed at Reaction_02016 (Cross of the Martyrs / Diplomatic Impunity) as the redirect pattern.

## Card Text

**Reaction:** When an opponent's ability would wound, move, or engage your character • Your performer at that location suffers those effects instead. *(If they are able)*

## Design Decisions

### Pattern: 02016 redirect, adapted to RiskReaction

Copied the clone-cancel-reemit flow from `Reaction_02016`:
- Intercept `EventCardEngaged`, `EventCardMoving`, `EventCharacterBeingWounded`, plus `EventCharacterIntervened` (duel intervention branch)
- Cancel pending event, offer redirect buttons for other characters at the same location
- `releaseEvent()` re-queues with new target id; intervene branch swaps `DUEL_DEFENDER` like 02016

**WHY not include heal/targeted/challenge/engarde events:** card text names only wound, move, engage. 02016 is broader because it's a general "redirect targeted ability" attachment; Altruistic is narrower.

### RiskReaction pay deferral

Unlike AttachmentReaction 02016 (free, resolves in `performReaction`), Altruistic discards from hand:
- `performReaction('redirect-*')` only queues pay events
- Actual redirect + `isValidTargetForAbility` check happens in `EventRiskReactionTriggered` (Pattern D.2 discipline — effect pairs with paid Risk)

**WHY no wound on redirect:** 02016 wounds the redirect target 1 as part of Cross of the Martyrs' cost; Altruistic text has no wound clause.

### "Performer at that location"

Matched Hexenjagd (`Reaction_01053`) semantics: `getCharactersAtLocationByPlayerId` at the target's location, excluding the character currently being affected. Player picks which other character takes the hit. "(If they are able)" enforced via `isValidTargetForAbility` before `releaseEvent`; invalid → cancel + notify (same as 02016/01014).

### Effect-based trigger (not targeting-based)

Card text says "would wound, move, or engage" — not "targets". **2026-07-05 fix:** Removed `IAbilityThatTargetsCharacters` gate from `shouldReactToEvent`. Now intercepts on the effect events themselves when source is opponent-controlled (`isOpponentAbility` checks source card or in-play action owner). On redirect:
- If ability implements `IAbilityThatTargetsCharacters` → `isValidTargetForAbility` for "(If they are able)"
- Otherwise → `releaseEvent` directly (maneuvers, forced effects, etc.)

### Decline behavior

Mirrored 02016: only re-releases on decline for `EventCharacterIntervened` (with `skipNextEvent`). Other event types left canceled if declined — same quirk as 02016; didn't "fix" since Eddie asked to follow that pattern.

## Files

- `modules/php/cards/faf/_03031.php` — wired `IHasReactions` + `Reaction_03031`
- `modules/php/cards/faf/reactions/Reaction_03031.php` — new

Not in StarterDecks yet (grep found no 03031 entry).

## Skill update (2026-07-06)

Captured learnings in `create-risk` skill as **Pattern D.4** — effect-event redirect RiskReaction, canonical ref `_03031`, comparison table vs `Reaction_02016`, `isOpponentAbility` helper, performer-at-location semantics, pay-deferral discipline.

## Feelings

Straightforward port once the Risk pay split was clear. The 02016 file is long but the structure is well-trodden. Slightly uneasy about decline-not-releasing for wound/move/engage — might be 02016 bug — but intentionally matched per user direction.
