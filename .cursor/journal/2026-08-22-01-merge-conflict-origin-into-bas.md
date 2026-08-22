# Merge conflict resolution: origin into bas

## Conflicted files
1. `modules/php/StatesTrait.php` — `stIssueChallenge` auto-engage list
2. `.claude/skills/create-character/checklist.md` — items 35+

## StatesTrait resolution
Kept **bas (HEAD)** list: NORMAL, SERVO_SCARPA, TORVO_ESPADA, AJA, DANILO, RAVEN, NO_MORE_WORDS.

WHY: origin only had the older NORMAL/SERVO/AJA subset. Bas added Torvo/Danilo/Raven/No More Words for Engage-printed challenge types. Dropping them would re-break auto-engage for those cards.

## checklist.md resolution
- Kept bas journal list (through Tomas/Raven/Hans/Benci/Danilo…)
- Kept bas detailed Térence ban text (item 36) but **removed Wilhelm conflation**
- Inserted origin's separate Wilhelm item (Combat-only *target* restriction) as 37
- Renumbered subsequent items 38–80

WHY: Wilhelm journal `2026-08-11-04` and references.md say `[Combat]` qualifies the *target restriction*, not a challenge-type ban. Bas checklist item 36 had reintroduced the misread ("Wilhelm (only Combat)"). Origin correctly split Térence ban vs Wilhelm target restriction — must keep that split while retaining bas's longer BAS checklist tail.

## Status
Conflicts resolved and staged. Merge commit not created — user didn't ask.
