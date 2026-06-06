# Wrath of the Don (01034) Audit

## Card Text
> **City Action:** Wound your performer • Target opposing en garde character may engage. If they do not, en garde your performer.

## Bugs Fixed

### Bug 1: `getPerformersForAction` was looser than `isAvailableToPlayer`

`isAvailableToPlayer` correctly required a performer to (a) be Engaged AND (b) have at least one opposing **en garde** character at the same location. `getPerformersForAction` only enforced (a). A player could therefore pick an Engaged performer whose only co-located opposing character was also Engaged, leading to an empty target list mid-action.

WHY this matters: the args filter at `HIGH_DRAMA_PLAYER_TURN_01034` correctly drops engaged opponents from the target list. So with the old filter, the UI would let the player pick a performer and then offer zero targets to choose from. The wound to the performer is queued *only* after a target is selected (good — no orphan wound), but the player would be stuck having to pass out of an action they shouldn't have been able to start.

Fix: dedup the predicate. `getPerformersForAction` now does the full filter (engaged + has at least one opposing en garde at location); `isAvailableToPlayer` just checks `count(getPerformersForAction(...)) > 0`. Single source of truth.

Also fixed the casing: `getCharactersinCityWithOpposingCharacters` → `getCharactersInCityWithOpposingCharacters`. PHP method calls are case-insensitive so the old spelling worked, but it's grep-hostile (no other call site uses the lowercase form).

### Bug 2: `isValidTargetForAbility` didn't enforce "en garde" on the target

The text says "opposing **en garde** character." The args correctly excluded Engaged characters from the UI list, but `isValidTargetForAbility` only checked controller + location. Defense-in-depth: any code path that consults `isValidTargetForAbility` directly (e.g. a future copy-card mechanic, tampered client payload) would have accepted an Engaged target.

Fix: added `if ($character->Engaged) return [false, "must be en garde"]`.

## Open Question (Not Fixed)

Both `isAvailableToPlayer` and `getPerformersForAction` require the **performer** to be Engaged. The card text doesn't explicitly demand this. The "en garde your performer" tail effect is a no-op if performer is already en garde, but the rest of the card (wound + force-opposing-engage choice) is still potentially useful.

I flagged this for the user but did not change it — they did not direct me to. If it later turns out to be a bug, the fix is to drop the `$performer->Engaged` filter from `getPerformersForAction` (and let `isAvailableToPlayer` re-derive from it).

## Things That Look Right (Verified, Not Changed)

- `IRiskThatTargetsCharacters` on `_01034`, `IAbilityThatTargetsCharacters` on `Action_01034`. Matches memory rule "if a card's Text targets a character, class must implement IAbilityThatTargetsCharacters."
- Extends `RiskAction` (not `RiskCityAction`) — same pattern as 01131 (already audited 2026-05-15), `getCharactersInCityWithOpposingCharacters` is strictly stricter than `RiskCityAction` so the override is intentional. Performer-in-city is enforced.
- States registered in all three places (`states.inc.php` map, `states.7s5s.php` definitions, `States.php` constants). JS handlers in `OnEnteringState.7s5s.js:715-733` and `OnUpdateActionButtons.7s5s.js:280-287`.
- Active player swap to target's controller on `01034` → `01034_2` transition via `createTransitionEvent($character->ControllerId, ...)`. Engage button on `01034_2` uses `actFromCardWithId {id: 1}`.
- Both branches in `01034_2` queue `createActionResolvedEvent`. Satisfies the pre-commit hook for `extends RiskAction`.
- Wound source/abilityId: `EventFactory::createCharacterBeingWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id)` — correct attribution to the Risk card.
- Engage/Engarde events route through `EventHub` which sets `Engaged` and emits the standard notify. Verified `EventCardEngaged` handler at `EventHub.php:568+`.

## Risk

Low. Bug 1's fix is strictly more restrictive (rejects a state where the action couldn't have completed anyway). Bug 2's fix is strictly more restrictive (rejects something the UI already filtered out). No legal play is removed; illegal/dead-end plays are blocked earlier.
