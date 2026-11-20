# Create Character skill — Serenissima lessons

## Why
User asked to fold `_05Serenissima` implementation lessons into create-character so the next agent does not re-learn: (1) any-trait `hasTrait` fiction, (2) Covert discard→Locker • En Garde Engaged bullet semantics, (3) discard•Parry + random adversary Technique, (4) Engarde factory trap.

## What landed
- **SKILL.md** shape rows: any-trait passive; Covert discard→Locker • En Garde; Technique discard • +1 Parry + random adversary discard.
- **pattern-a**: "Consider Owner to have any trait" + Daniella contrast updated to mention Patricia.
- **pattern-c**: subsection + Action examples table row; Engarde vs Engage factory table; bullet cost-vs-effect WHY.
- **pattern-e**: "Discard • +N Parry; adversary discards at random".
- **references**: `_05Serenissima` / Action / Technique entries.
- **checklist**: 18a any-trait; 109c Covert discard•Engarde; 109d Technique; 103 CAD id list + Serenissima state digits + Robin collision.

## Load-bearing WHYs to keep
- User corrected: Engaged is **not** a cost (right of `•`) — but Engarde **targets** must still be Engaged (picker + offer softlock gate).
- Engarde = `createCardEngagedEvent`; Action_01152a wrongly uses Engaged — do not copy.
- Any-trait fiction only helps callers that pass `$queryCard` (same systemic limit as Uwe).
- CardNumber 16 vs Robin: attachment has no HD/duel pickers → `405016` / `52105016` free.
- Random adversary discard = `array_rand`, not 01093 adversary picker.

## Unfinished
None — skill update complete.
