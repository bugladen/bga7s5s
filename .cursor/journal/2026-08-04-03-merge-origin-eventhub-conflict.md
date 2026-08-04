# Merge origin into bas — EventHub conflict

## Context
Eddie merging `origin` into `bas`. Sole conflict: `EventDuelGambleCardsRevealed` in `EventHub.php` — comment-only, same region as yesterday's cherry-pick onto main (`2026-08-03-07`).

## Conflict shape
- **HEAD (bas):** both WHY comments — `addCardToWorld` (Unravel `_04010`) + `cards[]` (gamble tooltip hydration). Functional code has `$theah->addCardToWorld($card)`.
- **origin:** only `cards[]` WHY. No `addCardToWorld` in that handler (main deliberately dropped it during cherry-pick of `3bedee0a` because main lacks `_04010`).

## Resolution
Kept HEAD's `addCardToWorld` WHY. WHY: bas still calls `addCardToWorld` here; dropping the comment would orphan the intentional call that makes deck-card Reactions see gamble reveals. The `cards[]` WHY stays either way (already on both sides).

Inverse of yesterday: on main we dropped the addCardToWorld WHY because the call wasn't there; on bas we keep it because the call is there.

## Status
Conflict resolved + staged. Merge not committed yet — waiting for Eddie if they want the merge commit.

Untracked `_04011.php` left alone (not part of this merge).
