> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern F — Forced at End of Planning

Use when the printed text is **`<b>Forced:</b> At the end of Planning • …`**. This is **not** scheme resolve — it fires later, from `stPlanningPhaseEnd` → `EventPhasePlanningEnd` → `PLANNING_PHASE_END_EVENTS`.

**Contrast — Reaction at end of Planning.** If the text is **`<b>… Reaction:</b> At the end of Planning • …`** (Look/Pass, optional), do **not** treat it as Forced. Use a `CardReaction` on `EventPhasePlanningEnd` that queues `createReactionTransitionEvent`, then (if needed) a follow-on pick state still registered under `PLANNING_PHASE_END_EVENTS` with `createTransitionEvent(..., "NNNNN", $reaction->Id)`. See [reactions.md](reactions.md) "Merchant / trait Reaction at Planning End". Reference: `_04025`.

### Trigger on the scheme class

```php
if ($event instanceof EventPhasePlanningEnd && $this->Location == Game::LOCATION_PLAYER_HOME)
{
    // Forced effect. Queue draws / transitions here.
}
```

WHY `LOCATION_PLAYER_HOME`: chosen schemes remain at Home until Dusk (see lifecycle above). Same gate as `_01098`.

### If the Forced needs a player pick — third transition map

| Piece | Where |
|---|---|
| State constant | `States::PLANNING_PHASE_END_<NNNNN>` = `28<NNNNN>` (append `2`, `3` for follow-on steps) |
| Transition map | `states.inc.php` → **`PLANNING_PHASE_END_EVENTS.transitions`** — key `"NNNNN"` |
| State class | `modules/php/States/<expansion>/State_planningPhaseEnd_<NNNNN>.php` (name: `planningPhaseEnd_<NNNNN>`) |
| Transitions back | `"" => States::PLANNING_PHASE_END_EVENTS` |
| JS keys | `planningPhaseEnd_<NNNNN>` in OnEntering / OnUpdate / OnLeaving |

Do **not** register these under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` — that map is only for resolve-time picks. Same card number key (`"03041"` / `"04025"`) can legally appear in both maps because they are separate lookups (see `_01098`: resolve `"01098"` vs end `"01098"`; `_04025`: resolve Renown pick vs Planning-End look/draw).

### Draw-then-discard subtype (`_03041`)

1. Compute N (e.g. 2, or 3 if `controlsAcademic` via `getCharactersInPlayByPlayerId` + `hasTrait("Academic")`).
2. Clamp N to drawable count: `countCardsInLocation(factionDeck) + countCardsInLocation(discard)`. If 0, notify and **return** — do not open a discard state or strip the existing hand.
3. Persist `public int $cardsToDiscard = $actualDraws` + `$this->IsUpdated = true`.
4. Queue N × `createCardDrawnEvent` **then** `createTransitionEvent($controllerId, $this->Id, "NNNNN")` (draws process before the state opens so `factionHand` includes them).
5. Discard state: `argsFromCard` exposes `cardsToDiscard` (also clamp to current hand size). `actFromCardWithIds` requires `count($ids) == $required`, re-validates each card is in the player's hand, queues `createCardDiscardedFromHandEvent(..., $asEffect = true)`, clears `$cardsToDiscard`, `nextState("")`.
6. JS: multi `factionHand` select; Confirm calls `onCardsDiscarded()` → `actFromCardWithIds`. Store count in `clientStateArgs.cardsToDiscard` on enter; in `EventHandlers.js` enable Confirm only when `getSelection().length === needed`.

Reference: `_03041` + `State_planningPhaseEnd_03041`. Opponent-pick Forced without draws: `_01098` + `State_planningPhaseEnd_01098`.

### Dual claim — you, then the chosen player (`_04051`)

When the Forced is **"claim a City location. Then, the chosen player claims a different City location"**:

1. **"The chosen player"** must already be on the scheme from resolve (`$chosenOpponentId`). Gate Forced: `$this->Location == LOCATION_PLAYER_HOME` **and** `$chosenOpponentId > 0`. If no claimable city for the controller (`canLocationBeClaimedBy`), notify, clear picks, and return — do not open an empty pick.
2. **Null-performer claim:** `createLocationClaimedEvent($playerId, null, $location)` (same as `Reaction_03005`). Offer only claimable city locs via `locationIds`.
3. **Two Planning-End states** under `PLANNING_PHASE_END_EVENTS` (`"NNNNN"` / `"NNNNN_2"`). Same card-number key may also exist on the resolve map — intentional (`_04051` resolve `"04051"` vs end `"04051"`).
4. After controller claims, stash `$claimedLocation` and queue `createTransitionEvent($chosenOpponentId, $this->Id, "NNNNN_2")` at `MEDIUM_PRIORITY` (claim event queued first so it processes before the opponent's pick opens).
5. **Contingent Then:** if the chosen player has no other claimable City location (`exclude` the just-claimed name), notify and skip state 2 — opening an empty pick soft-locks Planning End.
6. Clear `$chosenOpponentId` / `$claimedLocation` after Forced completes and on `EventCardSentToLocker`.
7. JS: both end states are filtered city-location choosers (`locationIds` from args) — same shape as resolve location picks.

Reference: `_04051` / `State_planningPhaseEnd_04051{,_2}`.
