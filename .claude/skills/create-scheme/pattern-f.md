> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern F — Forced at End of Planning

Use when the printed text is **`<b>Forced:</b> At the end of Planning • …`**. This is **not** scheme resolve — it fires later, from `stPlanningPhaseEnd` → `EventPhasePlanningEnd` → `PLANNING_PHASE_END_EVENTS`.

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

Do **not** register these under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` — that map is only for resolve-time picks. Same card number key (`"03041"`) can legally appear in both maps because they are separate lookups (see `_01098`: resolve `"01098"` vs end `"01098"`).

### Draw-then-discard subtype (`_03041`)

1. Compute N (e.g. 2, or 3 if `controlsAcademic` via `getCharactersInPlayByPlayerId` + `hasTrait("Academic")`).
2. Clamp N to drawable count: `countCardsInLocation(factionDeck) + countCardsInLocation(discard)`. If 0, notify and **return** — do not open a discard state or strip the existing hand.
3. Persist `public int $cardsToDiscard = $actualDraws` + `$this->IsUpdated = true`.
4. Queue N × `createCardDrawnEvent` **then** `createTransitionEvent($controllerId, $this->Id, "NNNNN")` (draws process before the state opens so `factionHand` includes them).
5. Discard state: `argsFromCard` exposes `cardsToDiscard` (also clamp to current hand size). `actFromCardWithIds` requires `count($ids) == $required`, re-validates each card is in the player's hand, queues `createCardDiscardedFromHandEvent(..., $asEffect = true)`, clears `$cardsToDiscard`, `nextState("")`.
6. JS: multi `factionHand` select; Confirm calls `onCardsDiscarded()` → `actFromCardWithIds`. Store count in `clientStateArgs.cardsToDiscard` on enter; in `EventHandlers.js` enable Confirm only when `getSelection().length === needed`.

Reference: `_03041` + `State_planningPhaseEnd_03041`. Opponent-pick Forced without draws: `_01098` + `State_planningPhaseEnd_01098`.
