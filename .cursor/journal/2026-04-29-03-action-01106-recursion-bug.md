# Action_01106 isAvailableToPlayer / getArgsFromAction infinite recursion

## The bug

`Action_01106` is "Play Target Risk From Opponent's Discard Pile". Its
`isAvailableToPlayer` iterates every opponent's discard, finds Risks, and for
each Risk asks each of its actions whether it is available to the active
player (with `overrideInHandCheck = true`).

Problem: if any opponent's discard contains a copy of card 01106, that card's
`Action_01106` instance gets asked the same question, with the same `$playerId`.
The inner call iterates the same opponents, finds the same 01106 in the same
discard, and recurses again. There is no visited-set, no depth guard, and
`overrideInHandCheck = true` defeats `RiskAction`'s location early-out.

User reported a session hanging on this card — that's the cause.

`getArgsFromAction` for state `HIGH_DRAMA_PLAYER_TURN_01106_2` has the same
shape (iterate the chosen opponent's discard, list each Risk's available
actions). It would loop the same way, AND it would display Action_01106 as a
selectable option on the chosen opponent's discarded 01106 — chaining 01106
onto a copy of itself isn't a real play.

## Fix

Skip `$action instanceof self` in both inner foreaches:

- `Action_01106.php` line ~76 (the availability check) — short-circuits the
  recursion.
- `Action_01106.php` line ~149 (the args/UI builder) — keeps a player from
  selecting a chained 01106-on-01106.

Left a WHY comment on the first occurrence explaining the recursion. Didn't
duplicate it on the second; the pattern is parallel and self-evident once the
first is read.

## Why this fix vs. alternatives

Considered:

- **Visited-set threaded through recursion.** Most general but requires
  changing the `isAvailableToPlayer` signature on the base interface, or
  smuggling state through `Theah`. Big surface area for a single-card bug.
- **Depth counter on Action / Theah.** Same surface-area problem, plus you
  have to pick a magic limit.
- **Skip self.** Tiny, local, and correct: a 01106 chosen recursively to
  re-target another 01106 isn't a meaningful play — by the time the inner
  call resolves, the outer call has already committed to playing _some_
  Risk. Letting the inner check return false is the right answer.

If a future card introduces a different action type with the same
"play a card from elsewhere → ask its actions if they're available" shape,
the same bug class will reappear. Worth keeping in mind, but YAGNI for now.

## Not fixed here

The `warn(...)` debug logs in `isAvailableToPlayer` (lines 30, 37, 48, 52, 56,
66, 90) are still present. They were presumably added during the bug hunt.
Leaving them — user didn't ask to remove them and they're cheap. If they're
noisy in prod logs the user can rip them out.

The `$adjustedWealth` computation is only assigned inside an `if ($owner
instanceof IWealthCost)` branch but used unconditionally below. If the owner
is ever not an `IWealthCost`, that's an undefined-variable warning. Not
related to the loop bug — flagging here for whoever audits this card next.
