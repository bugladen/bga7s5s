# Elena Agnelli (`_03004`) — Hungry Soul implementation

Character (not Leader) in the faf expansion. Vodacce / Sorcerer / Strega /
Pirate / Spy. Stats 5/2/0/2.

## Card text

- **Passive:** Elena has +1[Finesse] for each **Sorcery** in her dueling
  line.
- **Technique:** If Elena's combat card is a **Sorcery** • +1[Parry] and
  wound the adversary.

## Passive Finesse bonus — recompute at EventDuelEndOfRound

Pattern A (passive `handleEvent`) with running state on the card.

`$FinesseBonus` tracks the currently-applied bonus. On
`EventDuelEndOfRound` we recount Sorcery cards in Elena's player's dueling
line and queue a `createCharacterFinesseModifedEvent` for the delta. On
`EventDuelEnd` we apply a reset to 0 — this fires BEFORE the dueling line
is cleared (see `StatesTrait::stDuelEnd` line 1663 queues `EventDuelEnd`
before queueing the discard events for the line), so the recount would
still see Sorcery cards. We bypass the recount entirely on `EventDuelEnd`
by calling `applyFinesseDelta(0, …)` directly.

### WHY recompute at end-of-round (not on a card-moved event)

There is no clean event fired when a combat card enters the dueling line.
`FrameworkActionsTrait::actDuelActionChooseCombatCard` (and the maneuver
paths) call `$this->cards->moveCard(...)` directly without firing
`EventCardMoved`. So we can't listen for "Sorcery entered my dueling line."

The closest reliable boundary is `EventDuelEndOfRound`, which fires after
both players' combat cards have resolved into the dueling line. Recomputing
there means the bonus is correct *before the next round's gambling* — which
is exactly when Finesse matters (gambles available = `ModifiedFinesse -
gamblesCount`, see `FrameworkActionsTrait::actChooseGambleCard`).

### WHY "her dueling line" requires Elena be a participant

`LOCATION_DUELING_LINE` is keyed per *player_id* in the deck table, not
per character. If Elena is in play but a different one of her player's
characters is dueling, that character's combat cards would land in the
same per-player dueling line — and a naive recount would credit Elena
with cards she didn't play. Card text says "her dueling line", so I gate
the recompute on `$this->Id == challengerId || $this->Id == defenderId`.
If she isn't a participant, the bonus resets to 0.

### WHY `createCharacterFinesseModifedEvent` (not a calc-time hook)

I considered hooking `EventDuelCalculateCombatCardStats` Yevgeni-style to
add per-round combat-card stats, but the text modifies *Finesse*, not
parry/riposte/thrust. The two consumers of `ModifiedFinesse` during a
duel are (1) gamble count and (2) any other card that reads Elena's
Finesse stat (e.g. Aja's intervention restriction in a *different*
challenge — unlikely mid-duel but possible). Modifying the underlying
`ModifiedFinesse` via the standard event is the only way both consumers
see it.

This follows the Soline el Gato (`_01089`) pattern of queueing raise/lower
Finesse events on duel boundaries. Soline is simpler — fixed -1 — and only
hooks the duel-started/end/swap events. Elena's bonus is dynamic, hence
the recompute at end-of-round.

### Counts non-combat dueling-line cards too

Maneuvers paid as cost also land in `LOCATION_DUELING_LINE` (see
`FrameworkActionsTrait` lines 1451/1512/1643). If any of those has the
Sorcery trait, it counts toward the bonus. Card text says "each Sorcery
in her dueling line" with no "combat card" qualifier, so this is correct.

## Technique — `Technique_03004`

`isAvailableToPlayer` gates:
1. In a duel.
2. Elena is the current round actor.
3. At least one combat card in the current round, controlled by Elena's
   player, has the Sorcery trait. ("Elena's combat card is a Sorcery.")

On `EventDuelCalculateTechniqueValues`:
- `$event->parry += 1` directly (the event has plain int fields with no
  `add*` methods — mirrors `Technique_01050` which does
  `$event->thrust -= 1`).
- Queue `createCharacterBeingWoundedEvent` against the adversary.

### WHY filter combat cards by ControllerId

`getCombatCardsForCurrentRound()` returns BOTH players' combat cards. The
text refers to *Elena's* combat card. Filtering on
`$card->ControllerId == $owner->ControllerId` is the standard pattern
(same shape Aja uses for "her" things — `$theah->getDuelRoundActor()` is
Elena, and her own combat card has her player as ControllerId).

### Not a Gambling Technique

Card text is "**Technique:**" not "**Gambling Technique:**". No
`Game::DUEL_GAMBLED` gate.

## Files touched

- `modules/php/cards/faf/_03004.php` — finished the constructor scaffold,
  added the `$FinesseBonus` running state, `handleEvent` for the passive,
  and the `Techniques` array.
- `modules/php/cards/faf/techniques/Technique_03004.php` — new.

## Pre-commit hook compliance

- No Action/Reaction subclass added — no `createActionResolvedEvent` /
  `setUsed` / `isAvailable` literal-string requirements.
- No `ISorcererAbility` (text doesn't carry the keyword for the Technique;
  Sorcery trait on the *combat card* is the gate, not a keyword on the
  ability).
- No `IAbilityThatTargetsCharacters` — the wound is automatic on the
  current adversary, not a player-pick "target a character" prompt.

## Open questions / risks

- **Combat card returned to hand mid-round.** If something pulls the
  combat card out of the dueling line BEFORE `EventDuelEndOfRound`, the
  recount catches it. If something pulls it AFTER, the bonus stays
  inflated until the next end-of-round. Acceptable: there's no event we
  can hook for arbitrary dueling-line departures (the moves happen via
  direct `cards->moveCard` calls).

- **Elena swapped INTO an in-progress duel.** Not addressed. The next
  `EventDuelEndOfRound` would recompute correctly from her player's
  dueling line — but if her player's prior duelist had Sorcery cards in
  the line, Elena would inherit that bonus. Strictly the card text says
  "her dueling line", but the per-player line semantics make this
  ambiguous. Flagging for QA. Same caveat for swap-out.

- **Duel ends with non-zero bonus.** Handled: `EventDuelEnd` calls
  `applyFinesseDelta(0, ...)` which subtracts the running bonus via a
  Finesse-modified event. The event fires before the dueling-line cards
  are discarded (per `stDuelEnd` ordering), so the math is clean.

- **Elena destroyed mid-duel.** Once destroyed her ControllerId is still
  set, but `EventDuelEnd` will still fire and reset the bonus. If the
  bonus needs to be undone mid-duel on destruction (so the swap-in
  replacement doesn't inherit it), that's not currently handled — but
  ModifiedFinesse on a discarded card doesn't affect anything anyway.

- **No JS / state wiring needed.** Pure passive + a technique that
  resolves through the standard `EventDuelCalculateTechniqueValues`
  pipeline. No new states, no `states.inc.php` entries, no `States.php`
  constants, no JS files touched.
