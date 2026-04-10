# Don Constanzo Scarpa (_01006) Audit

Thorough audit of all three abilities on this Vodacce Leader card. Spent significant time tracing the Dusk phase event ordering to verify the reaction timing.

## Key Finding: Everything Works

All three abilities correctly implemented. The only fix was a cosmetic indentation issue on line 86.

## Dusk Phase Timing (Important for future reference)

The Dusk phase state machine flow is:
1. DUSK_PHASE_BEGIN → fires EventDuskPhaseBegin
2. DUSK_PHASE_CLEANUP → characters move home, cleanup city
3. DUSK_PHASE_DISCARD → players discard to panache
4. DUSK_PHASE_END → fires EventDuskPhaseEnd (Reaction_01006 triggers here)
5. DUSK_END_OF_DAY → fires EventDuskEndOfDay, then inline loop discards Brutes

The reaction fires at step 4, removes Brute trait. At step 5, the event is queued but NOT dispatched before the discard loop runs. So the character without Brute survives the loop. The Brute class re-adds the trait during DUSK_END_OF_DAY_EVENTS processing (after the loop), which means the character gets Brute back for the next day but isn't discarded.

WHY this matters: `queueEvent` does NOT dispatch immediately — events are processed when `stRunEvents` runs in the next *_EVENTS state. This is a critical pattern for understanding timing-sensitive reactions.

## "Player Home" Shared Location

All players' characters go to `Game::LOCATION_PLAYER_HOME` which is the string `'Player Home'`. In the physical card game, each player has a separate home zone. The code uses controller filters to replicate this separation when checking "characters at location."

The reaction's `$character->ControllerId == $don->ControllerId` filter looks like it might be overly restrictive (card text just says "Target character") but it's actually correct because during DuskPhaseEnd, ALL characters are at "Player Home" — without the filter, you'd see every player's characters.

## Pressure Calculation Note

The +1 bonus is applied once per pressure stat type in the `pressureLocation` loop. The notification says "for each Pressure Type" which matches. The card text just says "add +1" — ambiguous, but the implementation and notification are consistent with each other.
