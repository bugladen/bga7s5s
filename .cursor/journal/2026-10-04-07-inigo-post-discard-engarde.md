# Íñigo Technique_03039 — post-discard hand compare

## Eddie's clarification (playtest)
1. Hand count for En Garde happens **after** the adversary discards
2. Then if Inigo's hand **<** opponent's hand → en garde Íñigo
3. Move Home at end of round is **unconditional** once the technique resolves — not tied to discard/engarde outcome

## Why change from count-1 in actFromTechniqueWithId
Old code compared `(adversaryHand - 1) > ownerHand` in the picker confirm, then queued discard + engarde together. Same arithmetic, but the "Then…" lived before the discard event ran — easy to misread as pre-discard and fragile if discard were canceled.

New shape:
- `actFromTechniqueWithId` sets `$PendingEnGardeCheck` + queues discard only
- `handleEvent(EventCardDiscardedFromHand)` (source = Íñigo, asEffect, not canceled) clears the flag and compares hands, excluding `event->cardId` because discard is `runEventHubAfterCards` (card still in hand during card handlers)
- `$MoveHome = true` still on `EventResolveTechnique` before the empty/non-empty branch

## Do not "fix"
- En Garde is mandatory when the inequality holds (printed effect). Eddie's "may" was casual — not a chooser state.
- Move Home `engage=false` stays — no Engage on the print.
- Empty adversary hand: still evaluate En Garde immediately (0 > owner never fires); Move Home still flagged.
