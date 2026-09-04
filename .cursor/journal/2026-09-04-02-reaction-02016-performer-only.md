# Reaction_02016 — redirect target is equipped character only

## Context
Prior notes (`2026-03-17-04`, `2026-07-01-02`) treated Cross of the Martyrs as "pick any ally at location as performer." Eddie now corrected: card text redirects only to the equipped character ("wound your performer • targets your performer instead").

## Bug
`getReactionButtonProperties` listed every controlled character at the location except the original target. `shouldReactToEvent` gated on "some other character exists," not "equipped can take the hit." Intervention branch (copied from Vittoria 01014) was backwards: fired when the *equipped* character was intervened onto, then offered redirect *away* to another ally.

## Fix
- Buttons: only equipped character (`getOwningCharacter`)
- Availability: skip when original target already is the equipped character
- Intervention: fire when an *ally* at location is intervened onto; redirect *to* equipped; `releaseEvent` swaps DUEL_DEFENDER from ally → performer
- Decline on intervention: re-queue cloned event unchanged + `skipNextEvent` (DUEL_DEFENDER already applied in `actHighDramaChallengeActionIntervene`; don't call `releaseEvent` or oldTargetId gets clobbered)
- `performReaction` UserException if chosen id ≠ equipped

## WHY not "Accept/Decline" only
Kept a single named redirect button for the equipped character so the existing `redirect-{id}` / wound / `isValidTargetForAbility` path stays intact. Same UX shape, one legal choice.

## Do not regress
`2026-03-17-04` claimed Eddie said performer = chosen character. That was wrong or outdated — trust current card text + this session. Altruistic (`Reaction_03031`) still allows any performer at location by *its* text; do not "fix" 03031 to match 02016.
