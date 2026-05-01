# Roll the Bones (01114) Audit

## Card Text
"**Maneuver:** Gamble for free and reveal an additional card if your participant is a Scoundrel. Add the chosen card's combat values to this one instead of playing it. Sink all gambled cards. *(Gambling for free does not count against your total gambles in a duel.)*"

## What Was Already Right
- Gambling for free not counting toward the duel total — `actGambleCardChosen` only updates `duel_round.gambled` for `GAMBLE_TYPE_NORMAL`, leaving `GAMBLE_TYPE_ROLL_THE_DICE` uncounted.
- The maneuver triggers the gamble flow via `ROLL_THE_BONES_ACTIVATED` → `stSetNextCombatCard` → `rollTheBones` transition → `DUEL_GAMBLE_REVEALED`.
- `+1` reveal for Scoundrel actors via `getNumberOfGambleCardsToReveal` override.

## What Was Broken
The chosen gamble card was being **played as a combat card** — exactly what "instead of playing it" forbids:
1. `actGambleCardChosen` moved the chosen card to `LOCATION_DUELING_LINE`.
2. It fired `CombatCardAnnouncedEvent` for the chosen card.
3. If the chosen card had maneuvers, it transitioned to `useManeuver` to let the player use them.
4. `stApplyCombatCardStats` ran a second time with `CHOSEN_CARD = gamble card`, inserting a second `duel_round_combat_card` row and attributing R/P/T to the gamble card rather than to 01114.
5. "Sink all gambled cards" was only half-done — only the *non-chosen* revealed cards were sunk; the chosen card never returned to the deck.

There was also a smaller bug in the maneuver itself: `IsActivated` was set to `true` in `EventResolveManeuver` and only reset on `EventManeuverCanceled`. After a successful Roll the Bones, that flag stayed `true` on the in-memory maneuver instance — meaning any subsequent gamble (in a later round) by a Scoundrel actor would silently get `+1` reveal as long as 01114 was still in `theah->cards`.

## The Fix

### State plumbing
- New global `Game::ROLL_THE_BONES_CARD_ID` — stores the Risk card's id while the special gamble flow is active. Cleaned up in `stApplyCombatCardStats` on use, plus `stDuelEndOfRound` and `stDuelEnd` for safety.

### `Maneuver_01114`
- Dropped the `IsActivated` instance variable entirely. The +1 Scoundrel detection now reads `GAMBLE_TYPE == ROLL_THE_DICE` AND `ROLL_THE_BONES_CARD_ID == owner->Id` from globals. WHY: the instance flag survived across rounds; using globals scoped to the active gamble eliminates the leak and naturally keeps multi-card stacking sane (each maneuver only matches its own owner id).
- `EventResolveManeuver` now sets `GAMBLE_TYPE`, `ROLL_THE_BONES_CARD_ID`, and `ROLL_THE_BONES_ACTIVATED` *before* calling `getNumberOfGambleCardsToReveal`, so the override has the globals it needs.
- Defensive `EventManeuverCanceled` cleanup clears the globals if they happen to be ours (matches the audit pattern from `2026-04-02-01-mireli-revision-cancel-bug.md`).

### `actGambleCardChosen` (FrameworkActionsTrait.php)
Branched on `GAMBLE_TYPE_ROLL_THE_DICE`:
- Sink the chosen card via `insertCardOnExtremePosition(..., false)` — fulfilling "Sink all gambled cards."
- Skip `CombatCardAnnouncedEvent` (the card isn't being played).
- Skip the `LOCATION_DUELING_LINE` move.
- Always transition `noManeuver` — never offer the gambled card's maneuvers.
- Fire `EventDuelPlayerGambled` so reaction listeners (e.g., Ivy's Sorcerer City Reaction) still see the gamble.
- Notify all players that the values were added to Roll the Bones.

### `stApplyCombatCardStats` (StatesTrait.php)
Branched on `ROLL_THE_BONES_CARD_ID > 0 && CHOSEN_CARD != ROLL_THE_BONES_CARD_ID && GAMBLE_TYPE == ROLL_THE_DICE`:
- `combatCardId` on the emitted `EventDuelCalculateCombatCardStats` becomes `ROLL_THE_BONES_CARD_ID` (so 01114 owns the stats per text).
- R/P/T values still come from `CHOSEN_CARD` (the gamble card) — that's the additive contribution.
- Skip the `INSERT INTO duel_round_combat_card` — only one combat card was actually played (01114).
- Skip the maneuver queueing path — there's no maneuver to chain off the gambled card.
- Clear `ROLL_THE_BONES_CARD_ID` and reset `GAMBLE_TYPE` to NORMAL after use.

WHY use `combatCardId = 01114` instead of the gamble card: reactions that target a specific combat card (e.g., things that look up `combat_card_id` from `duel_round_combat_card`) should find 01114, since that's the only card "played" this round per the text.

## Verification Checklist (Manual)
- [ ] Play 01114 as combat card → choose its maneuver → gamble → confirm chosen card sinks (does not appear in dueling line)
- [ ] Confirm the chosen gamble card's R/P/T are added to round totals attributed to 01114
- [ ] Confirm gambled card's maneuvers are not offered
- [ ] Confirm `duel_round.gambled` is unchanged (gamble didn't count)
- [ ] Confirm Scoundrel actor reveals N+1 cards; non-Scoundrel reveals N
- [ ] Confirm a normal gamble in a *later* round of the same duel still reveals the base count (no Scoundrel +1 leak)

## Files Changed
- `modules/php/Game.php` — added `ROLL_THE_BONES_CARD_ID` constant
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01114.php` — full rewrite of state model (globals not instance)
- `modules/php/FrameworkActionsTrait.php` — `actGambleCardChosen` ROLL_THE_DICE branch
- `modules/php/StatesTrait.php` — `stApplyCombatCardStats` ROLL_THE_DICE branch + cleanup in `stDuelEndOfRound` / `stDuelEnd`
- `modules/php/theah/events/EventDuelCalculateCombatCardStats.php` — `statsAddedToExistingCombatCard` flag
- `modules/php/theah/EventHub.php` — flag passed in `updateRoundWithCombatStats` notif and message text varies on flag
- `modules/js/Notifications.js` — `notif_updateRoundWithCombatStats` skips card placement when `statsAddedToExistingCombatCard` is set

## Followup: Visual Card Placement Bug
First pass missed that the second `EventDuelCalculateCombatCardStats` (with `combatCardId = 01114`) caused `notif_updateRoundWithCombatStats` to render another card image in the duel row's combat slot. The R/P/T totals updated correctly but a duplicate 01114 image appeared (which from the user's POV looked like the gamble card was being placed in the dueling line).

WHY a flag instead of a separate notification: the existing handler does both the visual placement AND updates the running R/P/T totals. We still need the totals update — the gamble card's stats have to show up in the round's combat R/P/T. Adding a `statsAddedToExistingCombatCard` flag lets us reuse the existing notification path while suppressing only the card placement and adjusting the message.

## Personal Note
The original implementation looks like an early stub — somebody set up the GAMBLE_TYPE plumbing and the maneuver class, but the actual "instead of playing it" semantics never got implemented. Searching for `GAMBLE_TYPE_ROLL_THE_DICE` showed it was set in exactly one place and read in exactly one place (the gamble-doesn't-count check). That was the tell that no one had finished the job.
