# Merge conflicts: bas ← origin (main)

Merging main into bas after Stand Your Ground. Three conflicted files.

## Resolutions

### pattern-a.md
Took **origin** Night-of-Drinking / `announceAction()` engage-cost guidance. HEAD still had the older EventActionTriggered "pay at announcement" wording. Origin's version matches the staged Action_03021/03034/03057 engage-at-announce fixes. WHY: 01109 deletes EventActionTriggered; costs must survive cancel.

### ArgumentsTrait.php
Combined both:
- **origin:** `$cannotRefuseDueToDaichi` via last-known challenger/defender (dead-participant safe)
- **HEAD:** `cannotRefuseDueToKnivesOut` client flag

Null-guard Knives Out on `$target !== null`. WHY pass live `$target` not last-known: destroyed defenders are out of city → location gate must be off; last-known still has city Location and would false-positive block refuse.

### FrameworkActionsTrait.php
Combined both:
- **HEAD:** `STAND_YOUR_GROUND_CHALLENGE_TYPE` refuse throw
- **origin:** `buildCity` + `getCharacterById` + `$performerId`/`$targetId` for last-known refuse-check path

Kept Knives Out (HEAD-only) after the merge hunk; added `$target !== null` guard for the same dead-participant reason.

## Status

Conflicts resolved and staged. Merge not committed — user asked only to fix conflicts.
