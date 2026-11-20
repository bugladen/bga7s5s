# Red Tape: hand-owner chooses four

User: "The four revealed cards should be owning player chosen" after confirming CharacterTargeted exists.

## Interpretation / WHY
Print: "Their controller reveals four cards from hand." Prior impl used Julius `array_rand`.
Ambiguity: project jargon "owning player" usually = ability owner (Premonition journal), but print actor for reveal is **their controller**. Implemented **hand owner chooses** (matches print); Red Tape owning player still sinks on `_3`.

If user meant Red Tape player peeks opponent hand and picks four — flip `_2` active to owner + private chooseList (Rum/Crystal Eye `_private` shape). Ask if they push back.

## Change
- `_2` = hand-owner factionHand multi-select exactly 4 (Quack handoff via createTransitionEvent)
- `_3` = owning-player chooseList sink (was old `_2`)
- hand ≤4: auto-reveal all, skip `_2`
- JS Confirm must not use `onCardsDiscarded` (would yank cards from hand UI on reveal)
- Skill B.15 / checklist / references / SKILL table updated; tests 8 pass

## Unfinished
None unless user wanted owning-player peek instead of hand-owner choose.
