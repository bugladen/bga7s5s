# Object of Wonder vs Lucas Damned Approach ban

## Report
Eddie: Object of Wonder prompted putting Lucas Martinez “Damned” into Approach after he died (Dawn Forced destroy). Damned’s text expressly forbids Approach.

## Root cause
`Reaction_01202::isValidCharacter` only excluded Leader / Mercenary / Brute. Damned is Monster/Undead/Pirate/Castille — passes the filter. Ban existed only in `DeckValidator` (construction), not in-game PutIntoApproach paths.

## Fix
1. `Character::canEnterApproachDeck()` — default `!hasTrait("Brute")`; `eventCheck` throws on `EventCharacterPutIntoApproachDeck` if false.
2. `_02032::canEnterApproachDeck()` → `false` (printed Forced).
3. `Reaction_01202` uses `canEnterApproachDeck()` instead of raw Brute trait check.

WHY predicate + eventCheck: prompt must not appear (predicate); Manipulative / any future PutIntoApproach still hard-blocked if someone forgets to filter (eventCheck). Same pattern as `canBeWoundedByOpponentAbilities`.

WHY Brute in the default method: DeckValidator already bans Brutes from Approach; Object of Wonder already excluded them. Centralizing means one gate for eligibility effects.

## Not changed
DeckValidator still hardcodes `02032` + Brute messages — construction UX unchanged. Could switch to `canEnterApproachDeck()` later.
