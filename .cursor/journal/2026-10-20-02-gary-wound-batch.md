# Reaction_05Gary held the wrong wound in an Action_01012 batch

Playtest: Sibella (Action_01012) targeted Katain (02011) at Rena's location. Katain was wounded immediately. The Rena prompt then fired, player accepted, Rena took a wound. User called the held wound "02012".

## WHY it was Sibella, not Turais

Action_01012 queues two MEDIUM BeingWounded events, in order: Sibella (the cost), then the opposing target. Reaction transition is REACTION_PRIORITY 6, which sorts *after* MEDIUM 3. Gary canceled Sibella, saved that clone, and queueEvent'd the offer. runEvents then drained Katain's still-queued MEDIUM wound. savedWoundEvent !== null made Gary ignore it, so Katain was wounded with no prompt. The prompt the player accepted was the clone already held — Sibella (_01012). The prompt text never named the victim, so it read as "some other character," and 02012 is one digit off 01012. Turais (_02012) is not wounded by this action. If a later report still names Turais after the prompt says the name, look for a different wound source.

## WHY stackEvent, not a higher priority constant

stackEvent sets priority to min(queued)-1, so the choice is the next event even when the batch was queued before the reaction ran. Raising REACTION_PRIORITY globally would reorder every reaction against MEDIUM costs. Same shape as Reaction_01090's "stack the offer ahead of the ability's own events."

Pass must stack the clone too. queueEvent appended it behind the rest of the MEDIUM batch, and the *next* victim consumed skipNextEvent. That victim landed, then the clone was offered — same symptom, on the decline path.

## WHY updateCardObjectInDb on pass

performReaction is outside runEvents. IsUpdated is only flushed at the end of an event inside runEvents. skipNextEvent would die with the request and the released wound would be offered again. setUsed already writes the row on accept; pass has no setUsed.

## Prompt

Description now appends "of {victim Name}" from savedWoundEvent so the player can pass the held victim and still catch the next one.

## Ownership filter (user override)

User later said the reaction triggers only on characters owned by the controlling player. That overrides the printed "any character at this location, including the opponent" reading above. Do not widen it back.

Check is `$victim->OwnerId != $owner->ControllerId`, not ControllerId. City deck cards are created OwnerId 0 / ControllerId 0, and recruit only writes ControllerId. A recruited city character the player controls is not owned by them. A faction character they own still qualifies if control has moved. Location check stays: ownership alone is not enough, and Rena herself is still skipped so the redirect cannot loop.

Card text on `_05Gary` now says "a character you own at this location" so the UI matches. Tests: opponent at the location is ignored; ControllerId match with OwnerId 0 is ignored; the stack-ahead / pass / accept cases use two characters that player owns. The original Sibella-then-Katain playtest no longer offers on Sibella (she is not owned by Rena's player).
