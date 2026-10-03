# First Player reaction order — implementation

## What shipped
First Player chooses order when ≥2 reaction transitions are next in the queue.

- `Event::$runImmediately` (already stubbed) + DB: `getNextEventByRunImmediately`, `peekNextEvent`, `getQueuedReactionTransitionEvents`, `setEventRunImmediately`
- `Theah::runEvents`: immediate first, then peek+divert to `chooseNext`, else FIFO
- 50 `*_CHOOSE_NEXT_ABILITY` states off every `*_EVENTS` — sole transition `"" => FOO_EVENTS`
- `argsChooseNextReaction` / `actChooseNextReaction` / JS `chooseNextReaction` buttons

## WHY these decisions

### runImmediately first in the loop
After FP picks, act only sets the flag and `nextState("")` back to EVENTS. `stRunEvents` must promote that row before the multi-reaction divert would fire again (otherwise FP would re-enter choose with the same set) and before FIFO would pick an older sibling.

### Peek before divert (not “any ≥2 reactions in DB”)
Higher-priority events (priority &lt; 6) can still cancel or change availability. Diverting as soon as 2 reactions exist anywhere in the queue would interrupt mid-batch. Only pause when the *next* event would be a reaction transition and siblings remain.

### CHOOSE_NEXT_ABILITY → EVENTS only (not reaction/pay)
Empty-transition rule + ownership: EVENTS already owns `"reaction"`/`"pay"`/`"endOfEvents"`. Calling `runEvents()` from the act while still in CHOOSE_NEXT_ABILITY would require duplicating those transitions on every CHOOSE_NEXT_ABILITY. Returning to EVENTS reuses the existing wiring. When Revealed choose-order uses the same “choose then drain via sibling EVENTS” shape.

### Merge immediate into full process path
An earlier stub did `handleEvent` + `continue` only. That never hits the `EventTransition` → `nextState($transition)` block at the bottom of `runEvents`, so the chosen reaction would never open `playerReaction`. Immediate events fall through the same hub/cards/transition path as normal dequeues.

### RiskReaction fog / consolidation
- Opponent single RiskReaction (buttons): `"{Player} - Risk Reaction"`.
- Multiple RiskReactions for a **non–First Player**: one button `"{Player} - Risk Reaction"`; click marks all that player's Risk transition events `runImmediately`.
- First Player's own RiskReactions are never consolidated — each button shows the full card/ability label so FP can order them individually.
- **Public choose-order log** (2026-10-02): fog **all** Risk names including FP's — see `2026-10-02-02-choose-next-reaction-log.md` (`fogAllRiskNames`).
- Other card abilities: `"{Player} - {Card Name} - {In-Hand|In-Play} - {Ability Name}"`.
- Framework reactions: `"{Player} - {Ability Name}"`.

### Skip divert if FIRST_PLAYER unset
Setup / early dawn can queue reactions before First Player is determined. Fall back to FIFO rather than `changeActivePlayer(0)`.

## ID scheme notes
Prefer `EVENTS_id + 3` (x0/x1/x2/x3). Collisions used next free after PAY (e.g. WHEN_REVEALED 248/249, IN_PLAY 467, IN_HAND 476, ACCEPT/REJECT 4596/4597, RECRUIT 4236, DUEL_COMBAT_CARD 5203).

### Peek / divert only at highest priority tier
`runEvents` takes `MIN(event_priority)` first, then counts reaction transitions **at that priority only**. chooseNext only if that count &gt; 1. Deferred siblings (priority 7) no longer re-open choose while pay/work at 6 drains. Args/act/notify use the same tier filter.

## Ambush pay interrupted by chooseNext (2026-09-30)

Bug: FP picks Ambush (01023) → Prevent Intervention → back at chooseNext instead of pay.

WHY: Ambush queues EnteringPay (MEDIUM=3) + pay transition (REACTION=6). Sibling reaction transitions were still at REACTION=6 with *older* event_ids, so after EnteringPay drained, peek saw another `reaction` and divert-to-chooseNext fired before pay.

Fix: `actChooseNextReaction` now demotes other queued reaction transitions to `DEFERRED_REACTION_PRIORITY` (7) when promoting the chosen one. Pay (6) then runs; remaining reactions are offered again after.

## Unfinished / watch
- Zombie First Player: no special auto-pick; BGA default may stall — add if it bites in play.
- LIKE match on serialized `s:8:"reaction"` is brittle if EventTransition field layout changes; fail-closed via instanceof check after unserialize.
- No live multi-reaction playtest yet in this session.
