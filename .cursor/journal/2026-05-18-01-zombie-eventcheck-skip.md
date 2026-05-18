# Skip eventCheck and Reaction handleEvent for Zombie Players' Cards

## What changed

1. `Theah::eventCheck()` skips the per-card `eventCheck($event)` call
   for any card whose `ControllerId` is a zombie player. Cards with
   `ControllerId == 0` (uncontrolled, e.g. city cards) are always
   processed.
2. `Theah::runEvents()` skips `$card->handleEvent($event)` entirely for
   cards whose controller is a zombie. (Originally landed inside
   `Card::handleEvent` gating only reactions; user asked to hoist the
   gate to the call site for efficiency. Now we skip the whole
   `handleEvent` call rather than just the reactions loop — see
   "Reversal" below.)
3. Added cached public helper `Theah::isPlayerZombie(int): bool` reading
   `player_zombie` from `loadPlayersBasicInfos()`. Cache is a private
   `?array` keyed by playerId, computed lazily on first call. Both
   call sites use this.

## WHY

A zombie player has left the game. The `ZombieTrait::doZombieTurn`
handler can only fire the simple default transition for whichever state
the zombie is dropped into. When a card the zombie controls has a
reaction or ability triggered by `eventCheck`, the resulting state needs
the zombie player to make interactive choices — but the zombie handler
can't do that, and many of those states aren't even listed in
`doZombieTurn`, so they throw `feException: "Zombie mode not supported
at this game state"`.

By skipping the eventCheck entirely for cards a zombie controls, we
prevent their cards from injecting new states/reactions into the event
flow. The event still propagates to other players' cards and city
locations.

## Alternatives considered

- **Skipping inside each card's `eventCheck` / each reaction's
  `handleEvent`:** Would require touching every card class (440+ files)
  and is easy to forget for future cards. Centralizing in
  `Theah::eventCheck` and `Card::handleEvent` is two chokepoints
  instead of hundreds.
- **Gating inside `Card::handleEvent` vs. at the call site in
  `runEvents`:** Call site wins — same number of zombie checks (one
  per card), but skipping the whole `handleEvent` function call avoids
  the dispatch overhead for zombie-controlled cards entirely. Also
  keeps zombie-filter logic colocated with the `eventCheck` filter, so
  future readers see both in the same file.

## Reversal: gating reactions only vs. whole handleEvent

Initial pass gated only the reactions loop inside `Card::handleEvent`,
on the theory that techniques/maneuvers/actions inside `handleEvent`
are passive state-update hooks and skipping them could leave state
stale. User then asked to hoist the gate to either the start of
`Card::handleEvent` or the call site — implicitly accepting that the
whole `handleEvent` should be skipped, not just reactions. Followed
that direction. If the staleness theory turns out to matter (passive
state updates getting missed for zombie players), we'd revisit and
push the gate back inside `handleEvent` around the reactions loop.
- **Using `in_array` on a list:** Used an `isset()`-keyed map instead
  because the outer loops iterate every card in play (potentially
  hundreds), and `in_array` is O(n) per card.
- **Recomputing zombie set per call vs. caching:** Cached. The set
  doesn't change mid-request and `Card::handleEvent` calls
  `isPlayerZombie` once per card per event — hundreds of calls per
  event burst is plausible.

## Open questions

- Should `Theah::queueEvent` / `stackEvent` also bail entirely if the
  *triggering* player is a zombie? Not addressed here — the user's ask
  was per-card filtering inside `eventCheck` and per-reaction filtering
  inside `Card::handleEvent`, not whether the event should fire at all.
- Cache invalidation: `Theah::isPlayerZombie` caches for the lifetime
  of the Theah instance. If a player flips to zombie mid-request, the
  cache could be stale. Unlikely in practice (zombie status flips
  asynchronously between requests) but worth flagging.
