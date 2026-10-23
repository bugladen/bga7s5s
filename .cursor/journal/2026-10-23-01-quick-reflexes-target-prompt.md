# Quick Reflexes prompt names the wounded character

User: Buratino's target never shows in the log, and the Quick Reflexes prompt does not say who would be wounded.

## Why the name was missing

Reaction_02059 cancels EventCharacterBeingWounded and holds a clone until the player accepts or declines. The wound notification in Character.php only fires on EventCharacterWounded, which the hub queues after BeingWounded finishes. So the reacting player sees the prompt before any "received a wound" line exists. If they ignore the wound, that line never happens.

Buratino (Action_01019) also queued the wound with no choice message, so the public log had no target either.

## What I changed

- Prompt and Ignore button use the held event's character name: "ignore a wound on %s". Fallback text stays if the event is gone, so a description call with no pending wound does not crash.
- Resolve log names that character via inject code.
- Buratino logs `${owner} targets ${target} to be wounded` at the moment of the choice, before the wound event can be canceled.

Looked up the character from savedWoundEvent rather than copying the name onto another field. The clone already survives the card serialize (private reaction state), and the character is still in the city when the prompt is built.
