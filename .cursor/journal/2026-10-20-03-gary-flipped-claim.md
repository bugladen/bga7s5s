# Reaction_05GaryFlipped is now a claim

Printed text (already on `_05GaryFlipped`) changed from En Garde on any opposing destroy to:

When Rena's adversary is destroyed • Claim this location.

## WHY adversary = the other duel seat

The old reaction treated "opposing" as a different controller, anywhere, and only if Rena was Engaged (En Garde is useless otherwise). That reading is dead. "Rena's adversary" names her, so she has to be a duel participant, and the destroyed character has to be the other seat.

Used challenger/defender ids, not getDuelRoundActor/Opponent. Same reason as Victorious (`_03073`): at destroy time the round actor can be the corpse. Gate on `IN_DUEL` so leftover duel rows after the duel do not keep offering.

A bystander at the location, and Rena herself, do not qualify. Destroying Rena still queues Leader `endOfGame`. That is not this reaction.

## WHY claim is not "uncontrolled only"

Text does not say uncontrolled. `canLocationBeClaimedBy` is the gate (Home fails `locationInCity`; Indomitable Will clears `CanBeClaimed`). An opponent who already controls the city location is still a legal claim. Do not add a controller `== 0` check the way `Reaction_04043` does — that card prints "uncontrolled".

Stash `$location` on the reaction and `IsUpdated` the owner. The click is a later request. "This location" is where Rena was when the adversary died.

If the location stops being claimable before the click, throw `UserException` before `parent::performReaction` so the window stays open and she can Pass. Pass does not `setUsed`. Claim does.

No En Garde, no Engage check. CAD prototype: no `clienttranslate`.

Test: `tests/regression/Reaction_05GaryFlipped_Test.php`.
