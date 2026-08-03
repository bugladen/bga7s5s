# Cherry-pick 3bedee0a conflict on main

## Context
Eddie was cherry-picking `3bedee0a` (Gamble Reveal hover hydration) from `bas` onto `main`. Only conflict: `EventHub.php` in `EventDuelGambleCardsRevealed`.

## Conflict shape
Comment-only conflict. Functional `cards[]` / property-array notify payload and the JS `Array.isArray` scan in `format_string_recursive_with_injection` already auto-merged cleanly.

- **HEAD (main):** handler from `b39ae9bf` — public log names only, no `addCardToWorld`.
- **Incoming (bas parent):** had `addCardToWorld` + WHY for Unravel the Thread `_04010` (deck-card Reactions must see revealed gamble cards; hub runs before cards). Cherry-pick only *added* the `cards[]` WHY next to that existing comment.

## Resolution
Kept the `cards[]` WHY (that's what this commit is about). Dropped the `addCardToWorld` WHY — that call does not exist on main's handler, so the comment would be orphaned/misleading.

WHY not restore `addCardToWorld` during this cherry-pick: out of scope. That's bas/Unravel work (`39b6bcc4`); main doesn't have `_04010` yet. Bringing it in would smuggle bas behavior into a tooltip-only fix.

## Result
Cherry-pick continued → `7e846698` on main. Working tree clean. Ahead of origin/main by 1.
