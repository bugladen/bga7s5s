# Reaction_02030a Pass → argsHighDramaInHandActionPay null fatal

## Bug
Pass on Fraternité! `Reaction_02030a` while in `HIGH_DRAMA_IN_HAND_ACTION_REACTIONS` (473) → `done` → `IN_HAND_ACTION_EVENTS` → empty queue → `endOfEvents` → `HIGH_DRAMA_IN_HAND_ACTION_PAY` (475) → fatal:

`Call to a member function getOwningCard() on null` at `ArgumentsTrait.php` (`argsHighDramaInHandActionPay`).

Stack confirmed: `performReaction` Pass → `stRunEvents` → `Theah::runEvents` → `nextState('endOfEvents')`.

## Root cause
Same stale-active class as Léontine (2026-06-05-02), different destination.

1. Reaction transition `changeActivePlayer(reactor)`.
2. Pass returns to type=`game` events hub.
3. `runEvents` drain-complete path only restored `CURRENT_PLAYER` when **current** state.type was `ACTIVE_PLAYER`.
4. Events hubs are always type=`game` → restore skipped → `endOfEvents` opened pay with reactor still active.
5. `getInHandActionById` searched reactor's hand for the turn player's Risk → null → `getOwningCard()`.

Léontine journal considered fixing runEvents broadly and rejected for blast radius; patched `stHighDramaInPlayActionDispatch` instead. That left every other `endOfEvents → turn-player activeplayer` hub (in-hand pay, equip pay, brute pay, …) exposed. Duel path already restored `DUEL_CURRENT_PLAYER` unconditionally — non-duel was the inconsistent one.

## Fix
1. **`Theah::restoreCanonicalActivePlayer`** — always re-seat `CURRENT_PLAYER` / `DUEL_CURRENT_PLAYER` before `endOfEvents` (no `ACTIVE_PLAYER` gate). Extracted for tests; `runEvents` calls it.
2. **`argsHighDramaInHandActionPay`** — belt-and-suspenders: re-seat `CURRENT_PLAYER` and pass that id into `getInHandActionById`.
3. **`getInHandActionById($id, ?int $playerId = null)`** — optional owner hand so pay args don't depend on stale active.
4. **FakeGamestate** — `changeActivePlayer` updates `Game::activePlayerId` (BGA parity) so harness catches this class of bug.

## Why not only harden args
Without runEvents restore, the wrong player would still be the active player for the pay UI even if args didn't fatal. Root fix is re-seating before `endOfEvents`.

## Tests
- `ArgumentsTrait_HighDrama_Test`: pay args with `activePlayerId=2`, `CURRENT_PLAYER=1`
- `Theah_Capabilities_Test`: restore CURRENT_PLAYER / duel / skipTransitions
- `Reaction_02030a_Test`: existing Pass path still green

## Unfinished / watch
Other pay args (equip/brute/recruit) still only rely on runEvents restore — fine if that holds. If another args path looks up by active hand without re-seat, same pattern.

## Is 02030a the only trigger?
No. Framework bug: any opponent reaction offered from a type=game *EVENTS hub, Pass → empty queue → endOfEvents → turn-player activeplayer state that trusts getActivePlayerId().

02030a is a frequent interrupter (any Action/Reaction/Technique/Maneuver Activated) but not unique. Same class:
- Léontine path (2026-06-05-02) — in-play confirm events → dispatch (patched at dispatch only)
- EnteringPayState discount/engage reactions under IN_HAND_ACTION_EVENTS if the reactor ≠ CURRENT_PLAYER (01116b/01133/02021/03013/01035v2 usually own-player so Pass is harmless)
- Nested case that fits this crash: turn player fires a pay-window reaction → ReactionActivated → opponent 02030a offers → Pass → in-hand pay fatal
- Equip/brute/recruit event hubs had the same stale-active exposure before the runEvents fix

Why rare: needs opponent reaction + Pass + hub that endOfEvents into a hand lookup / pay UI. In-play path often failed softer (wrong player on UI) and was partly papered by dispatch re-seat.
