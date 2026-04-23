# duelParticipantWoundsTaken NULL Return Bug

## The Error
```
Theah::duelParticipantWoundsTaken(): Return value must be of type int, null returned
```

## Root Cause
`SUM(wounds_taken)` in the SQL returns NULL when there are no matching rows. This happens in the first round of any duel, because the query filters `round <> $round` - there are no prior rounds to sum. The method has `: int` return type, so returning the raw NULL from `getUniqueValue()` triggers a TypeError.

## Fix
Added `COALESCE(sum(wounds_taken), 0)` and `(int)` cast. This matches the identical pattern used by `getCurrentRoundThrust()` and `getCurrentRoundRiposte()` at lines 1499-1517 in the same file. Those methods were already written correctly; this one was just missed.

## WHY: COALESCE + cast, not just cast
Belt and suspenders. COALESCE handles the SQL-level NULL, the cast handles any edge case from `getUniqueValue()`. The nearby methods do both, so this stays consistent.
