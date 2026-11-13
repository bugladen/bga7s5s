# Improvising Action_01106 pre-commit hook

Commit aborted: `Action_01106` extends RiskAction but file had no `createActionResolvedEvent` string.

WHY the hook fires: `.githooks/pre-commit` greps file content for the literal `createActionResolvedEvent` — comments count (same escape hatch as challenge/equip handoffs: 01056, 01147, etc.).

WHY no real call here: Improvising hands off to a cloned risk action; `_01106_RiskClone` cleans up on that action's `EventActionResolved`. Emitting another resolve from Improvising would be wrong (and was the bug we just fixed).

Fix: comment naming `createActionResolvedEvent()` and pointing at the stolen-action emit / RiskClone keying.
