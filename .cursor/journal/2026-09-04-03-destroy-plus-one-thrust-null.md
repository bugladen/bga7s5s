# Technique_DestroyPlusOneThrust — null Id on challenge reject

## Crash
Zombie `actHighDramaChallengeActionReject` → `GENERATE_THREAT` → `EventGenerateChallengeThreat` → line 71 `$character->Id` on null.

Stack is expected: reject still runs GenerateThreat (rejection-wound math; see Bastien journal `2026-05-12-05`). DestroyPlusOneThrust was listed there as "only tweaks threat numbers" — wrong; it also destroys the attachment.

## Root cause
Challenge branch always:
```
$character = $this->getOwningCharacter(...);
$unequipEvent = ...($character->Id, ...);
```
Duel branch already guarded with `$owner instanceof Attachment && $owner->isAttached()`.

`getOwningCharacter()` is null when Owner is an unattached Attachment (or attach target missing). Challenge path had no guard → fatal.

Also: Character-owned copies (Katain Reaction_02011 / Yepikhodov 03051 clone Throwing Knife onto a Character). Duel path correctly skipped destroy; challenge path would have tried to unequip the Character as an attachment.

## Fix
Shared `queueDestroyOwner()`:
- Only destroy if owner is Attachment
- Unequip via `$owner->AttachedToId` (no Character object / no null->Id)
- Discard even if already unattached (still "destroy this card")
- Same path for duel + challenge

## WHY not skip destroy on reject
Technique was activated when the challenge was issued; cost/effect still apply to rejection wound threat. Only the NPE/guard was wrong — not whether destroy runs on refuse.

## Do not regress
Bastien `CHALLENGE_ACCEPTED` guard on 01063Swap is separate — that technique mutates duel roles; this one correctly still mutates threat + destroys on reject.
