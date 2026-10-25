# No Rest count() fatal during Cat's Embargo acknowledge

User's table was in planningPhaseEnd_01098_2. Stack was Reaction_04025.php count(57) from State_planningPhaseEnd_04025::getArgs. Those fit together: acknowledging Embargo returns to planning-end events, and the already-queued No Rest pick then builds args.

## Why count(57)

Cat's Embargo writes the revealed card id into the shared chosenCard global (`_01098` actFromCardWithId). No Rest's Look used that same global for the JSON list of peeked faction cards. Embargo can run after Look and before the pick state, so chosenCard is 57 when getArgs runs.

json_decode("57") is the integer 57. `57 ?: []` keeps 57 because it is truthy. count(57) fatals. Do not "fix" this by casting or by reading chosenCard again.

## What I changed

Looked-at cards live in `reaction04025LookedCards`. getArgs / act / zombie never read chosenCard.

If that key is empty (this live table already lost the JSON), lookedCards peeks the faction deck again. The pick has not moved those cards, and Embargo only reveals a hand card, so the top of the deck is still the look. That peek is what unsticks the current game after they leave 01098_2.

Do not move the list back onto chosenCard to match 03052. 03052 is not interleaved with Embargo's acknowledge state.
