# Cross of the Martyrs + Bleed Out — redirected wound canceled

## Bug
Opponent Bleed Out (01160) targets wounded ally. Defender uses Cross of the Martyrs (02016) on equipped character at same location: take Cross cost wound, redirect Bleed Out onto Cross holder. Cross wound lands; Bleed Out wound does not.

## Root cause
`Reaction_02016::performReaction` queues Cross's cost `EventCharacterBeingWounded`, then immediately calls `$ability->isValidTargetForAbility` on the performer. Bleed Out requires `Wounds > 0`. Cost wound is only queued — `Character::Wounds` is not incremented until `EventCharacterWounded` runs later in `runEvents`. Unwounded Cross holder fails validity → `cancelEvents` drops the cloned Bleed Out wound. Performer only gets the Cross cost wound.

Matches audit intent in `2026-03-17-04` ("wound happens, then redirect") but that note assumed queued == applied. It does not.

## Fix (revised per Eddie)
Remove `isValidTargetForAbility` on redirect entirely — always `releaseEvent` after Cross cost wound.

WHY not the temporary Wounds bump: Eddie wants no post-redirect validity gate. Card text forces retarget; chasing per-ability edge cases (wounded-only, Leader, city, etc.) is the wrong shape.

Also removed now-dead `loadAbility`, `cancelEvents`, and `savedAbilityId`/`savedSourceId` (only existed to feed that check). `shouldReactToEvent` still gates on `IAbilityThatTargetsCharacters` for *whether Cross can fire*.

## Follow-up: Decline orphaned the original wound
After redirect-accept worked, Eddie retested Bleed Out + Decline on Cross. Original target never took the wound.

Root cause (noted but left alone in `2026-03-17-04`): intercept clones+cancels the effect; decline only re-released `characterIntervenedEvent`. Wound/engage/move/heal/challenge clones stayed canceled → ability fizzled.

Fix: on non-intervention decline, `releaseEvent($game, $this->targetCharacterId)` + `skipNextEvent` so Cross does not immediately re-fire. Do not `setUsed` on that path — decline is not using the reaction. Intervention decline path unchanged.

## Do not regress
- Intervention decline path unchanged (`skipNextEvent` + re-queue as-is).
- Altruistic (`Reaction_03031`) still has its own validity check — separate card; do not "fix" it unless Eddie asks.
- Altruistic likely has the same non-intervention decline hole; fix only if reported.

## Files
- `modules/php/cards/tac/reactions/Reaction_02016.php`
