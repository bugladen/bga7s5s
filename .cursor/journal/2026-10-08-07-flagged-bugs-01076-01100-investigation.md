# Investigation: Flagged-not-locked from 01076–01100 regression pass

User asked: are the "Flagged-not-locked" items actual bugs?

## Fixes applied

### Round 1 — 01090 + 01098
### Reaction_01090 OVERRIDE
Clears on any `EventPlayerTurnEnd` while OVERRIDE is set. WHY: old `EXTRA_ACTIONS > 0` guard never matched production (`stNextPlayer` only emits turn-end when EXTRA_ACTIONS is already 0). Tests updated.

### _01098 / Reaction_01098
- Locker: early-return if `EmbargoedCardId == 0` or card missing (no TypeError)
- Forced: skip chooser when no opponent has hand cards; args omit empty hands; act rejects self + empty hand
- Reaction: listen for `EventCardAddedToCityDiscardPile` (city attachment discards)
- Suites green: Reaction_01090, Action_01090, Reaction_01098, Card_01098 (68 pass)

### Round 2 — remaining real + client-trust
- **01095b**: implements `IAbilityThatDependsOnNotBeingFirstPlayer` (Lorenzo can offer)
- **01096**: `cardInCity` gate; act re-checks attachment + controlled
- **01086**: act re-checks empty/Mercenary (still soft-notifies Indomitable block)
- **01099a**: listen `EventCardDiscardedFromPlay`; draw via `ControllerId`
- **01076**: companion controller/location/self validation before Sorcerer Start
- **01079**: step2 rejects id not in {1,2}
- **01091**: reject empty / >2 / duplicate target ids
- Suites green: 01095b/96/86/99a/76/79/91 (130 pass)

Not bugs (left alone): 01082 threat side, 01100 both directions.

## Verdict table

| Flag | Verdict | Severity |
|---|---|---|
| Maneuver_01082 threat side inverted | **NOT a bug** | — |
| Maneuver_01079 step2 non-1/2 | Client-trust gap only | Low |
| Action_01076 step2 no companion loc check | Client-trust gap | Low |
| Action_01086 act skips Mercenary eligibility | **Real bug** (cheatable) | Medium |
| Action_01095b missing IAbilityThatDependsOnNotBeingFirstPlayer | **Real bug** | Medium |
| Reaction_01090 OVERRIDE may stick | **Real bug** | High |
| Action_01091 no distinct/max/empty validation | Client-trust / soft-lock | Low–Med |
| Reaction_01099a missing FromPlay; draw via getActivePlayerId | **Partial real** (FromPlay gap) | Medium / Low |
| _01098 locker EmbargoedCardId=0; empty hand array_rand; pick self | **Real crash bugs** + trust gap | High / Med / Low |
| Reaction_01098 missing EventCardAddedToCityDiscardPile | **Real gap** (audit claimed fixed) | Medium |
| Action_01096 no in-city gate; act no attachment recheck | **Real rules bug** (Home) + trust | Medium / Low |
| Reaction_01100 both directions | **NOT a bug** | — |

## WHY details

### 01082 — not inverted
`challengerThreat` / `defenderThreat` are each participant's *own* threat (StatesTrait: actor takes `$challengerThreat` or `$defenderThreat` wounds). Final Strike adds +2/Lethal to the *non*-destroyed side = the adversary. Matches card text. Test comment was wrong to doubt EventHub semantics.

### 01090 OVERRIDE clear is dead code in production
`Reaction_01090` clears OVERRIDE only on `EventPlayerTurnEnd` when `EXTRA_ACTIONS > 0`.
`stNextPlayer` only emits `EventPlayerTurnEnd` in the `else` branch when `EXTRA_ACTIONS` is already 0.
So the clear condition can never fire in the real state machine. Action_01090's EXTRA_ACTIONS=1 was meant to pair with this, but timing is inverted.
Regression test fabricates `EventPlayerTurnEnd` with EXTRA_ACTIONS still 1 — pins unreachable state.
Leak: First Player keeps OVERRIDE for later abilities that day.

### 01095b vs Lorenzo
Patricia's City Action literally branches on first player and already reads OVERRIDE, but class does not implement `IAbilityThatDependsOnNotBeingFirstPlayer`. Lorenzo only offers on that interface. So Lorenzo never helps Patricia. Real functional miss (Action_01093 does implement it correctly).

### 01096 Home
City Action peers (01091/92/94/97) gate with `cardInCity`. 01096 does not. `getAdjacentCityLocations(Home)` returns Docks/Forum/Bazaar/… so Raton can activate from Home. Rules bug.

### 01098 / 01099a vs April audits
2026-04-10 Cat's Embargo audit claimed `EventCardAddedToCityDiscardPile` was added to Reaction_01098 — **not in current code** (only Hand + FromPlay).
2026-04-09 Shifting Blame audit claimed FromPlay among three listeners — **current 01099a is Hand + CityDiscard only** (no FromPlay).
Both are real coverage gaps vs generic "discards" text and vs prior audit claims (regression or never landed).

### 01098 crashes
- Locker handler: `getCardObjectFromDb(0)` when EmbargoedCardId unset → TypeError. `getCatsEmbargoData` null-guards; locker path does not.
- `array_rand($hand)` on empty opponent hand → PHP warning/fatal.
- args exclude self; act does not re-validate opponent id.

### 01100 both directions
Text: "When a character at this location accepts a challenge". Accept = defender. Challenge at a location always has an acceptor there. Wearer as challenger or acceptor both fit; effect needs your side in the duel. Both directions correct.

### Client-trust cluster (01076/79/86/91/96 act)
Common pattern: eligibility in `isAvailable`/`getArgs`, not re-checked in `act*`. 01086 is the worst because `canLocationBecomeUncontrolledBy` only checks the CanBecomeUncontrolled flag — Mercenary/empty rule is entirely skipped on commit.
