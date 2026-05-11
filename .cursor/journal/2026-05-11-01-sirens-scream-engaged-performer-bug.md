# Siren's Scream (_01179) — Stuck-state bug when only engaged performer

## Bug Report
User reported: A player got into state `highDramaInPlayActionChoosePerformer` with no valid performer. The player had one character at the location, but it was engaged. They were still allowed to select and confirm the 01179 City Action, then got stuck — no performer choices to make.

## Root Cause
`EventCityAction::isAvailableToPlayer` (the parent) only checks "any character in play at this location owned by player?" — it does **not** filter out engaged characters.

`Action_01179::getPerformersForAction` correctly filters out engaged performers (per the card text "Engage your performer" — an already-engaged character cannot be re-engaged).

The mismatch meant: isAvailableToPlayer returned `true` → action shown to player → they confirm → game transitions to `highDramaInPlayActionChoosePerformer` → `getPerformersForAction` returns an empty array → stuck.

## Fix
In `Action_01179::isAvailableToPlayer`, also assert `count($this->getPerformersForAction(...)) > 0`. Same filter, single source of truth, cheap call.

WHY this specific approach (vs. duplicating the engagement filter inline): The class already has the canonical filter in `getPerformersForAction`. Calling it directly guarantees they can't drift apart again — if a future rule change adds more constraints (e.g. "performer can't have X trait"), both checks stay aligned automatically.

## Previous Audit Note
The 2026-03-28 audit (`2026-03-28-04-sirens-scream-01179-audit.md`) said all was correct. The audit noted "Action_01179::getPerformersForAction further filters to unengaged performers" but didn't trace that `isAvailableToPlayer` doesn't apply the same filter — a blind spot worth remembering for future audits.

**General pattern to watch for:** When a subclass overrides `getPerformersForAction` to add filtering, the corresponding `isAvailableToPlayer` should reflect the same filter, or it must call `getPerformersForAction` and check non-empty. This is a likely class of latent bug across other cards that filter performers but inherit isAvailableToPlayer from parent.

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01179.php` — added empty-performers check to `isAvailableToPlayer`.
