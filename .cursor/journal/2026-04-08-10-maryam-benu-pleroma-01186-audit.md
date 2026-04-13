# Maryam Benu Pleroma (01186) Audit

## Card Text
> Negotiable (You may parley when paying for this card.)
> **Forced:** The first time a risk targets Maryam each Day • Cancel the effects. (Costs are still paid.)
> **Technique:** During the adversary's next round, they cannot use Maneuvers.

## Bug 1: Forced ability didn't block wound events from risks

`handleEvent` intercepted `EventCardMoved`, `EventCardEngaged`, and `EventChallengeIssued` — but NOT `EventCharacterBeingWounded`. Two risks implementing `IRiskThatTargetsCharacters` fire wound events directly:

- **Bleed Out (01160)**: "Target a wounded non-Leader character • Wound them." Fires `EventCharacterBeingWounded` with `sourceId` = the risk card.
- **Razrushitel (01138)**: When performer is already engaged, it moves to target and wounds them instead of offering the engage/move-home choice. Also fires `EventCharacterBeingWounded`.

WHY this was missed: The original implementation only considered the three most common risk-on-character effects (move, engage, challenge). Wounding as a direct risk action effect is less common, but it's still a risk targeting Maryam.

Fix: Added an `EventCharacterBeingWounded` handler block following the same pattern — checks `$event->characterId == $this->Id`, verifies `$event->sourceId` is a `Risk` implementing `IRiskThatTargetsCharacters`, then cancels the event and marks the ability as used.

## Bug 2: Technique flag `CancelOpponentManeuvers` not persisted to DB

When `EventResolveTechnique` set `$this->CancelOpponentManeuvers = true`, the owning card (Maryam) was NOT marked `IsUpdated = true`. The flag existed only in memory.

WHY this matters: BGA processes each player action as a separate HTTP request. After Maryam's player activates the technique, events are processed and the request ends. On the opponent's next request, Maryam's card is loaded fresh from DB — with `CancelOpponentManeuvers = false`. The opponent could use maneuvers freely despite the technique.

WHY event ordering doesn't save us: `EventTechniqueActivated` (which calls `setUsed(true)` → `updateCardObjectInDb`) is queued BEFORE `EventResolveTechnique` and processed first (same priority = FIFO). So the card is saved to DB before the flag is set. The flag is then set in memory by `EventResolveTechnique` but never re-saved.

Compare with `Technique_01204` which correctly does `$attachment->IsUpdated = true` after setting its `ReduceAdversaryParry` flag. Same pattern was already used in the reset blocks (`EventDuelNewRound` and `EventDuelEnd`) of this technique — just missing from the activation block.

Fix: Added `$maryam->IsUpdated = true` after setting `CancelOpponentManeuvers = true`.

## Bug 3: Technique didn't handle EventTechniqueCanceled

If the technique was canceled (e.g., by a reaction), the `CancelOpponentManeuvers` flag was never reset. The technique would continue blocking maneuvers even though it was supposed to be canceled. Pattern matches `Technique_01036` which resets its `MoveDaniela` flag on `EventTechniqueCanceled`.

Fix: Added `EventTechniqueCanceled` handler that resets the flag and marks the card as updated.

**General rule for future audits:** Any Technique that stores runtime state (flags, values, etc.) beyond the base `Used` property MUST handle `EventTechniqueCanceled` to reset that state. If the technique is canceled and the state isn't cleared, the effect persists illegitimately. Check for this in every technique audit.

## Everything else checked out
- Negotiable: `$this->Negotiable = true` ✓
- Stats: Resolve 5, Combat 4, Finesse 3, Influence dashed, WealthCost 6 ✓
- Traits: Mercenary, Duelist, Weapons Master, Ashur ✓
- Forced ability: condition reset on `EventDuskEndOfDay` (once per Day) ✓
- Forced ability: existing handlers for EventCardMoved, EventCardEngaged, EventChallengeIssued all check sourceId != 0 and verify source is Risk + IRiskThatTargetsCharacters ✓
- Technique: `isAvailableToPlayer` checks `IN_DUEL` ✓
- Technique: `eventCheck` blocks `EventResolveManeuver` when `adversaryId == maryam.Id` and flag is active ✓
- Technique: flag reset on `EventDuelNewRound` when Maryam is the actor (her round starts = opponent's round ended) ✓
- Technique: flag reset on `EventDuelEnd` ✓
- Technique: `CancelOpponentManeuvers` is a public property on the technique, serialized with the card ✓

## Reviewed all 21 IRiskThatTargetsCharacters cards
Categorized by what events they fire against targeted characters:
- **Engage** (EventCardEngaged): 01034, 01058, 01105, 01026, 01029, 01104 — all caught ✓
- **Move** (EventCardMoved): 01058, 01026, 01172, 01162, 01115, 01055, 01104, 01138, 01133 — all caught ✓
- **Challenge** (EventChallengeIssued): 01078, 01131, 01056, 01083, 01033 — all caught ✓
- **Wound** (EventCharacterBeingWounded): 01160, 01138 — previously NOT caught, now fixed ✓
- **Attachment destroy** (01174): targets attachment, not character — N/A
- **Heal** (01175): targets own character, beneficial — N/A

## Files Changed
- `modules/php/cards/_7s5s/_01186.php` — added EventCharacterBeingWounded import and handler block
- `modules/php/cards/_7s5s/techniques/Technique_01186.php` — added `$maryam->IsUpdated = true` on technique activation, added `EventTechniqueCanceled` handler
