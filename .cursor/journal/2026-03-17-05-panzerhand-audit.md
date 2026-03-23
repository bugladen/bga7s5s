# Panzerhand (_02017) Audit

## Context
Eddie asked me to audit Panzerhand against its card text. This is an Eisen Offhand attachment from the TAC expansion with a Reaction and a Technique.

## What I found

### Main class (`_02017.php`) - Clean
- OffHand = true ✓
- Traits: Armor, Eisenfaust ✓
- Riposte=1, Parry=0, Thrust=2
- Has both Reactions and Techniques wired up ✓

### Reaction (`Reaction_02017.php`) - Clean
Card text: "When equipped participant's adversary announces a combat card • It has -1 [riposte]."

The logic correctly:
1. Triggers on `EventCombatCardAnnounced`
2. Identifies the actor (announcer) via `getDuelRoundActor()`
3. Gets the adversary of the actor
4. Checks that the equipped character IS that adversary (meaning the announcer is the equipped character's adversary) ✓
5. Only offers the reaction if the combat card has Riposte > 0 (optimization - no point reducing 0)
6. Applies -1 riposte via `EventDuelCalculateCombatCardStats` using `removeRiposte(1)` ✓

### Technique (`Technique_02017.php`) - Clean
Card text: "The adversary cannot activate Techniques during their next round."

Initially thought there was a bug because `eventCheck` blocks all technique activations without checking who's activating. Considered adding a `$event->playerId != $owner->ControllerId` guard. Eddie pointed out this is unnecessary: duel rounds alternate with only one player acting per round. The flag is set during the owner's round and active during the adversary's next round — the adversary is the only one who could possibly try to activate techniques while the flag is set. Reverted the change.

**WHY the timing logic is correct:** The `NoTechniquesThisRound` flag is set when the technique resolves (owner's round), and reset on `EventDuelEndOfRound` when the ending player is NOT the owner (i.e., after the adversary's round ends). So the flag persists exactly through the adversary's next round — matching "during their next round."
