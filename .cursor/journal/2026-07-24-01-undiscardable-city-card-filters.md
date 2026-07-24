# Undiscardable city card filters (02007 / 01072 / 01112)

## Problem

Siren's Scream (`_01179`) can sit uncontrolled in the city with Renown on it and still refuse discard via `eventCheck`. Three abilities that offer "discard a City Card" treated any uncontrolled city card as a valid target:

- **Arson `Action_02007`**: availability / performer gate / discard transition / chooser all used `!isControlled()` only. Lone 01179-with-Renown looked like a discard target; if that was the only "effect half" and location also had 0 Renown, you could still open a chooser with an undiscardable card (or count it toward availability incorrectly).
- **Réputation Méritée `Action_01072`**: already had a **None** path when `targetCardIds` is empty, but the filter did not exclude undiscardable cards — so None stayed disabled while the only selectable card could not actually discard.
- **Carnaval `Action_01112b`**: worse — `isAvailable` counted all uncontrolled city cards (including 01179), but `getArgs` excluded **all** `CityEventCard`s. Lone Siren's Scream → action offered → empty chooser, no Pass → stuck.

## Approach

Declared `ICityDeckCard::canBeDiscardedFromCity(): bool`. Default `true` lives in `CityDeckCardTrait` (shared by `CityCharacter` / `CityAttachment` / `CityEventCard`). `_01179` overrides to `Reknown == 0`. Removed the method from base `Card` — it is city-deck-only.

WHY interface + trait instead of Card:
- Only city deck cards are discard targets for these effects; putting it on Card lied about the type.
- Callers already (or now) gate with `instanceof ICityDeckCard` before calling.

WHY not hardcoding `_01179` in each Action:
- Future undiscardable city cards only override the method; choosers already filter.
- Avoids try/catch dry-runs of `eventCheck` in availability loops.

WHY not keep 01112b's blanket `CityEventCard` ban:
- Card text is "available City Card"; Eddie previously clarified available ≈ uncontrolled.
- Only Siren's Scream has the Renown discard lock. Other city events are legitimate Carnaval targets. The blanket ban was a blunt (and stuck-state-causing) stand-in for that one case.

## Per-action behavior after fix

| Card | When only undiscardable city card(s) |
|---|---|
| 02007 | Counts as **no** discardable cards. Still playable if location Renown > 0 (wound + remove Renown, skip discard). Not playable if neither Renown nor discardable cards. |
| 01072 | `targetCardIds` empty → existing None button enabled → muster without discard. |
| 01112b | Action not available. Defensive: if somehow triggered with zero discardable cards, resolve without transition. |

Also tightened `actFromActionWithId` to throw if a non-discardable card is submitted.

## Do not "fix"

Do not remove `_01179::eventCheck` — dusk cleanup and other discard emitters still need the hard block. `canBeDiscardedFromCity` is the **chooser/availability** mirror, not a replacement for the event gate.

## Follow-up: 02007 Pass button

Eddie: still need a Pass on `highDramaPhase02007` — at least one live game can be stuck in that chooser (in-progress before filter, or transition race). Added:

- JS: Pass (`id: 0`), enabled only when `args.ids.length === 0` (same enable/disable pattern as 01072 None)
- PHP: `id == 0` resolves action if no discardable cards; rejects Pass when discardable cards remain

WHY keep Pass disabled when cards exist: Arson still requires discarding when a legal target is present — Pass is only the stuck-state escape, not an optional decline.
