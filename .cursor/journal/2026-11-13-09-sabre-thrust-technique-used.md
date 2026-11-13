# Sabre +1 Thrust stuck Used across challenges same Day

## Report
Terrell with equipped Sabre `_04054`. First challenge via No More Words announced Sabre +1 Thrust (worked). After that duel, second challenge same Day — technique crossed out / not announceable even though once-per-duel should have reset.

## Root cause
Techniques default `ResetOnDuelEnd=true`, `ResetOnDayEnd=false`. Challenge ActivateTechnique sets `Used=true` immediately. Clear was only on `EventDuelEnd` via `Technique::handleEvent`.

Gaps:
1. **Refuse / fizzle / cancel** never fire `EventDuelEnd` → Used stuck for the rest of the Day (dusk does not clear Techniques).
2. **Safety miss on completed duels** — if Used was set on a getTechniqueById DB-fallback instance while the attachment later missed the EventDuelEnd `$this->cards` walk, DB stayed Used=true. Same class as Dame faction-deck / deck combat-card Maneuver stuck-Used (already had combat-card soft-clear).

User said the first duel finished, so (2) or a cancel/refuse misremembered as "duel" could both match "crossed out on second challenge." Fixing both.

## Fix
- `Technique.php`: also clear `ResetOnDuelEnd` Used on `EventChallengeRejected` (refuse = no duel; reopen once-per-duel Techniques).
- `UtilitiesTrait::clearResetOnDuelEndTechniquesForCharacter` — character + equipped attachments, DB fallback + addCardToWorld, `setUsed(false)` when Used.
- Call sites: `stDuelEnd` (safety-net after queueing EventDuelEnd), challenge cancel (`stChallengeActionCheckCancelled`), accept fizzle, reject resolution.
- `FakeGame` mirror of the helper (no UtilitiesTrait on harness).

## WHY not ResetOnDayEnd on PlusOneThrust
Rules are once per duel, not once per day. Day-end reset would be wrong for Technique_02043a-style (`ResetOnDuelEnd=false`). Keep the flag semantics; widen the *exit paths* that honor `ResetOnDuelEnd`.

## Tests
- `Technique_04054a`: EventDuelEnd reopens; EventChallengeRejected reopens; still in challenge picker after clear.
- `StatesTrait_DuelEnd`: stDuelEnd reopens Used Sabre thrust on challenger attachment.

## Do not regress
- Technique_02043a / ResetOnDuelEnd=false must stay Used across DuelEnd (helper gates on ResetOnDuelEnd).
- Engage +1 Riposte (04054b) availability is Engaged/IN_DUEL — unrelated to this Used stick.
