# Elena Finesse stuck after duel (absolute NewFinesse race)

## Report
Elena `_03004` (base FIN 0) played several Sorcery combat cards (+1 FIN each
via dueling-line passive) plus Assassin's Garb `_04006` (+1 while adversary
wounded). Duel ended; Elena still showed **7 FIN**. Expected: base 0 (Garb is
duel-scoped only).

Same table / scenario as today's Soline floor fix (`2026-10-05-09`).

## Root cause
`EventHub` on `EventCharacterFinesseModifed` did:
`ModifiedFinesse = max(0, $event->NewFinesse)` — **absolute write**.

Every caller builds Old/New from a live snapshot then queues. On `EventDuelEnd`
several sources handle the same event and each queue a clear from that same
pre-clear FIN:

1. Soline raise (undo −1) → New = FIN+1
2. Elena `applyFinesseDelta(0)` → New = FIN−FinesseBonus
3. Garb clear → New = FIN−1

Events process later; **last absolute NewFinesse wins**. Elena's reset and
Garb's −1 do not compose. Sticky elevated FIN (often ~Garb's snapshot New, or
unchanged 7 if a clearer skipped).

WHY this looked like "Elena didn't reset": she did queue a reset and even set
`$FinesseBonus = 0` immediately; the later absolute Garb/Soline write put FIN
back up while the tracking flag was already cleared — so a second pass would
also no-op.

## Fix
EventHub applies `delta = NewFinesse − OldFinesse` against the **live**
`ModifiedFinesse`, still clamped with `max(0, …)`. Notify uses actual
before/after so `notif_characterFinesseModifed` stays truthful when deltas
chain.

## WHY delta in the hub (not per-card immediate mutate)
All FinesseModified producers already speak in Old→New deltas (Elena, Garb,
Soline, Benci-shaped passives). Fixing the consumer once fixes every multi-
source same-tick stack (duel end is the loud case). Per-card "mutate before
queue" would only paper over callers we remember to touch.

## Related (do not regress)
- Soline Absorbed (`2026-10-05-09`) still required for the **floor** case
  (queued −1 from FIN=0 stores no reduction). Delta hub does not replace that.
- Combat/Influence hub handlers still absolute-set — same footgun if multiple
  clears queue on one tick (Benci etc.). Out of scope for this report; same
  pattern if it surfaces.

## Files
- `modules/php/theah/EventHub.php` — FinesseModifed handler only
