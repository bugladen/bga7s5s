# Night of Drinking vs Diplomatic Impunity — deep code trace

Continuing from `2026-11-04-01`. Deep trace of the entire event pipeline: `actPayForInHandAction` → `announceAction` → `EventActionActivated` queue → `stRunEvents` → `runEvents` event loop → `handleEvent` dispatch → `Reaction_01109` offer → `stackEvent` reaction transition → transition validation → state change.

## Full flow verified as correct

Traced every step for the normal in-hand Action_02029 play:

1. `actPayForInHandAction` (FrameworkActionsTrait:1144) calls `moveCard` to Purgatory (line 1205), then `announceAction` (line 1207).
2. `CardAction::announceAction` (CardAction:73-94) creates `EventActionActivated` with `sourceId = owner.Id` (Risk _02029), queues via `Theah::queueEvent`.
3. `queueEvent` runs `eventCheck` (all cards' eventCheck methods) — no card throws on EventActionActivated. None of the ~20 eventCheck overrides handle this event type. Event inserts into DB.
4. Three more events queued: EventRiskPlayed, EventActionTriggered, EventCardDiscardedFromHand.
5. `nextState("actionPaidFor")` → `HIGH_DRAMA_PLAYER_TURN_EVENTS` → `stRunEvents` → fresh `buildCity` → `runEvents`.
6. `buildCity` loads Purgatory (line 133) and Hand (line 135). Both `_02029` (Purgatory) and `_01109` (Hand) are in `$this->cards`.
7. EventActionActivated dequeued (priority 3, first by FIFO event_id). Broadcast to all cards.
8. `_01109::handleEvent` → `Card::handleEvent` dispatches to `Reaction_01109::handleEvent`.
9. Gate checks: `isAvailable()` ✓, `owner.Location == HAND` ✓, `getCardById(sourceId)` returns `_02029` from Purgatory ✓, `instanceof Risk` ✓, opposing player ✓, `!hasTrait("Sorcery")` ✓ (Flourish/Bureaucracy/Camaraderie), `!effectsCannotBeCancelled()` ✓ (Risk base returns false, _02029 doesn't override).
10. De-dupe: `areTransitionEventsOfTypeForPlayerQueued(controllerId, "Reaction_01109")` — checks events table via `LIKE '%Reaction_01109%'`. If no match → stacks reaction transition at `MIN(priority) - 1 = 2`.
11. Next loop iteration: reaction transition (priority 2) dequeued, passes card/reaction/Locker validation, `nextState("reaction")` → `HIGH_DRAMA_PLAYER_TURN_REACTIONS`.

Every step checks out for the described scenario. No code-level fault that would silently skip the offer.

## Strongest finding: LIKE substring collision in de-dupe

File: `DB.php:332-337`:
```php
public function areTransitionEventsOfTypeForPlayerQueued(int $playerId, string $reactionType): bool
{
    $sql = "SELECT COUNT(*) FROM events
            WHERE (event_serialized LIKE '%EventTransition%'
              AND event_serialized LIKE '%{$playerId}%'
              AND event_serialized LIKE '%{$reactionType}%')";
    return $this->getUniqueValue($sql) > 0;
}
```

When `Reaction_01109` passes `"Reaction_01109"`, the LIKE `'%Reaction_01109%'` ALSO matches `"Reaction_01109v2"` because the shorter string is a prefix. If _01109v2's reaction transition is already in the queue, _01109 sees a false positive and skips.

This was KNOWN. `Reaction_01109v2` lines 81-86 document it: "the original card's LIKE '%Reaction_01109%' also matches that string." Journal `2026-10-22-05` records the decision to leave it unfixed: "Tightening the original LIKE would be a behavior change to a card the user did not ask to touch."

### Triggering conditions

- Both `_01109` and `_01109v2` in the same player's hand (same ControllerId)
- _01109v2 iterated BEFORE _01109 in `foreach ($this->cards)` (depends on card_id order in DB; higher card_id = created later = iterated later in InnoDB's default PK order)
- Both react to the same EventActionActivated

If _01109 has a lower card_id (likely — original created before v2 variant), _01109 iterates first, stacks first, and the collision doesn't trigger. But DB order isn't guaranteed by the SQL (no ORDER BY in `getCardObjectsAtLocation`).

### Why this might not be the answer

The user confirmed "card 01109 not 01109v2." If they only have _01109, no v2 transition exists to collide with. The collision is inert without v2 in the same hand.

### Contrast with other LIKE checks in the file

`areRiskReactionTriggeredEventsQueuedForSource` (DB.php:411-420) uses a TIGHT pattern: `LIKE '%sourceId\";i:{$sourceId};%'` with the PHP serialization delimiters. The de-dupe check for transitions does NOT use this pattern — it matches bare substrings.

## Other possible explanations (unchanged from prior entry)

1. **User misidentified card** — actually had _01109v2, which by design does not cancel Maneuvers. If the play was a Maneuver (not a Diplomat Action), v2 correctly skips.
2. **Stale `Used` flag** — `isAvailable()` returns `!Used`. If NoD was previously used this day and wasn't dusk-reset (mid-day deck recycle), reaction appears unavailable.
3. **Copy/proxy path** — if the Diplomat Action was triggered via a character's copy ability, `EventActionActivated` might never be queued (proxy paths queue `EventActionTriggered` directly from a Character sourceId, failing the `instanceof Risk` gate). User says "played from hand" so this should not apply, but confirmation may be imprecise.

## No code change made

Investigation only. The LIKE collision is a known accepted risk; the only new finding is verifying the full pipeline end-to-end has no other fault.
