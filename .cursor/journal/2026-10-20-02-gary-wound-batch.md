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

Description now appends "of {victim Name}" from savedWoundEvent so the player can pass Sibella and still catch Katain. Printed text is any character at the location, including the opponent — do not narrow it to allies.
