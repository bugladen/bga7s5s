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

## Status (first wave)

Conflicts resolved and staged. Merge not committed — user asked only to fix conflicts.

---

## Second wave (same merge, skill docs)

Five skill-doc conflicts after continuing `origin` → `bas`. Theme everywhere: HEAD had richer BAS skill content; origin renamed destroy-attachment to `createAttachmentDiscardedFromPlayEvent`.

### Resolution strategy
Keep HEAD's BAS rows/sections; adopt origin's factory name. WHY: Damya/Eager Blade bug class — teaching `createCardDiscardedFromPlayEvent` alone skips city discard Forced listeners.

### Per file
- **create-character/SKILL.md:** kept Danilo trait-stat aura + Technique destroy rows; destroy recipe → helper.
- **create-character/checklist.md:** kept items 60–99; item 60 took origin's "do not gate availability on attachments" (Action_03051 fix); item 79 → helper.
- **create-faction-attachment/SKILL.md:** kept Ciphered Tome renown rows; Forced destroy → helper.
- **helpers.md:** both — attachment helper + hand-discard factory.
- **pattern-b.md:** origin destroy-chain wording + kept entire HEAD Pattern B''' section.

## Status (second wave)

All conflicts fixed and staged. Git says "All conflicts fixed but you are still merging." Still not committed — user only asked to fix conflicts.
