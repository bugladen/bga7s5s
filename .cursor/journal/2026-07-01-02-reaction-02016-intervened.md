# Reaction_02016 EventCharacterIntervened

Eddie asked to apply the Reaction_01014 EventCharacterIntervened pattern to Cross of the Martyrs (Reaction_02016).

## What 01014 does

When Vittoria becomes the intervention target (`newTargetId == owner`), clone the event, cancel it, offer Thug swap (hand then in-play), then on confirm re-emit with updated `oldTargetId`/`newTargetId` and swap `DUEL_DEFENDER` + `CHOSEN_TARGET`. Decline releases original intervention with `skipNextEvent` to avoid re-trigger loop.

## What 02016 needed (adapted)

Same intervention intercept flow, but 02016 redirects to **any other character at location** (not Thugs). Reuses existing `redirect-{id}` buttons and wound-on-confirm performReaction path.

Key differences from 01014:
- Uses `getOwningAttachment` for IsUpdated/transition (attachment reaction)
- Gating is `count(characters at location excluding intervening character) > 0` instead of thugsInHand/thugsInPlay
- No second-step moveHome / muster flow — single redirect pick like other 02016 branches
- `loadAbility()` is null for intervention (no saved source/ability) — added `else if ($this->characterIntervenedEvent)` in performReaction to release anyway, matching 01014's inPlayThug branch

## releaseEvent for intervention

Copied 01014's DUEL_DEFENDER swap logic verbatim — intervention is duel-specific so this is correct. Renamed `$thug` to `$performer` for clarity in 02016 context.

## Decline path

Added decline branch for characterIntervenedEvent: release with original owning character id + skipNextEvent, same as 01014 inPlayThug decline.

Feels clean — 02016's existing redirect UI didn't need a separate flag beyond characterIntervenedEvent + targetCharacterId.
