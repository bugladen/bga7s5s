# Rosine Friese (_01041) — Dusk Influence Bug

## The Bug

User report: at dusk when everyone moves home, Rosine gains +1 influence incorrectly. Whether or not an opposing sorcerer was at her location at the time of dusk doesn't matter. Individual moves home (e.g., via her own City Action targeting a sorcerer) lose influence correctly — only dusk is broken.

## Root Cause

`Game::LOCATION_PLAYER_HOME = 'Player Home'` is a **single shared string** — every player's home shares the same location string. `Theah::getCharactersAtLocation("Player Home")` therefore returns every home character across every player, not the home of one specific player.

`_01041::getOpposingSorcererCount($location)` filtered by `isNotControlledByPlayer`, but it called `getCharactersAtLocation` first — so when `$location == "Player Home"`, the count saw opposing sorcerers across *all* opponents' homes.

This was invisible during normal play because Rosine's passive doesn't fire on locations like `"Player Home"` in isolation. But at dusk, `stDuskPhaseCleanup` queues `EventCardMoving` for every character whose Location != home, all targeted at the shared `"Player Home"` string. Combined with `EventCardMoved::runEventHubAfterCards = true` (handlers run before the move applies), two failure paths emerge — and at least one of them hits regardless of move-event ordering:

**Order A: Rosine processed first, then opposing sorcerer Bob**
- Rosine's `CardMoved` fires. fromLoc=Theatre, toLoc=home. Bob still at Bay (his old loc).
- fromCount at Theatre = 0, toCount at "Player Home" = 0. No fire (yet).
- Hub moves Rosine to "Player Home".
- Bob's `CardMoved` fires. Rosine.Location now "Player Home". Bob still at Bay.
- Line 89 (`toLocation == this->Location`): Bob's toLoc="Player Home" == Rosine.Location="Player Home" ✓ (string match!).
- character is opposing sorcerer ✓; count at "Player Home" = 0 (Bob hasn't moved yet) ✓. → **+1 fires. WRONG.**

**Order B: Bob processed first, then Rosine**
- Bob's `CardMoved` fires. Rosine still at Theatre, Bob still at Bay. No location matches at lines 89/98. Hub moves Bob to "Player Home".
- Rosine's `CardMoved` fires. fromLoc=Theatre count=0; toLoc="Player Home" — but Bob is now there, count=1.
- Line 79 condition `from==0 && to>=1` → **+1 fires. WRONG.**

Either way Rosine erroneously gains +1 — and you don't even need an opposing sorcerer to have shared her city location during dusk. *Any* opposing sorcerer in play will end up at the shared `"Player Home"` string, which is enough.

## Why individual move-home worked correctly

When Rosine's City Action moves a single sorcerer home, no other characters are simultaneously routed to `"Player Home"`. Bob's `CardMoved` fires; at handler time Bob is still at Theatre (Rosine's location), so line 98 (`fromLocation == this->Location`) matches, count is 1, → -1 fires correctly. Bob is the only character touching "Player Home" in that batch, so the conflation doesn't bite.

## Fix

Two changes in `_01041.php`:

1. **`getOpposingSorcererCount`**: short-circuit to `0` when the queried location is `LOCATION_PLAYER_HOME`. Rationale: the passive is "while opposing in the city" — characters at home aren't contesting anything, so a count there is meaningless. This catches Order B (Rosine sees toLoc=home count=0).

2. **`handleEvent` early-return**: after the self-move block, return if `$this->Location == LOCATION_PLAYER_HOME`. Rationale: lines 89–149 all compare `$this->Location` to event locations; once Rosine herself is at "Player Home", those equality checks would falsely match cross-player home traffic. The self-move block stays above the guard because at handler time Rosine's Location is still her old (city) location — `runEventHubAfterCards = true` means handlers run before the move applies. This catches Order A.

The 2026-03-30 audit fixed three other bugs in this card. The pattern across all four bugs is the same: handlers were structured around "is there a sorcerer at this location string" without accounting for which characters actually count. The shared-home-string conflation is the same flavor of bug — just a different specific manifestation.

## Why I went with the helper short-circuit + early-return rather than per-line guards

I considered adding `&& $event->toLocation != LOCATION_PLAYER_HOME` to line 79's +1 branch and `&& $this->Location != LOCATION_PLAYER_HOME` to lines 89/98/etc. That would work but means six near-identical guards scattered through the handler. The semantic — "Rosine's passive treats home as not-a-location" — is better expressed once at the helper level (covers the count) and once at the early-return (covers the location-equality checks). Future handlers added to this card automatically inherit the correct behavior.

## Did NOT touch

- `Action_01041` (the City Action) — verified correct in the 2026-03-30 audit. Targeting and the engage+move-home effect are unrelated.
- The other 3 prior bug fixes — those handle different edge cases (mustered/destroyed/recruited at Rosine's actual city location). Still valid.
