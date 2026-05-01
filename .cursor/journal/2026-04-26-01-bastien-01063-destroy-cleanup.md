# Technique-on-Destroy Cleanup — _01063, _02022

## Bug

Characters that grant techniques to friendly characters at their location were not cleaning up those techniques when destroyed. Affected cards:

- **_01063 (Bastien Girard)** — grants `Technique_01063Swap` to all friendly characters at his location
- **_02022 (Lord Stranahan III)** — grants `Technique_GainLethal` (as `Technique_02022`) to Musketeers at his location

Both already handled add/remove for recruitment and movement (EventCardMoved, EventCharacterRecruited), but not EventCharacterDestroyed.

## Fix

Added `EventCharacterDestroyed` handlers to both cards. Each mirrors the existing removal logic from the card's EventCardMoved handler:
- _01063: iterates friendly characters at location, calls `removeSwapTechnique()` on each
- _02022: iterates friendly Musketeers at location, removes `Technique_02022` via `getTechniqueByClassId` / `removeTechnique`

The _02022 handler uses inline removal (matching its existing EventCardMoved pattern) rather than a helper method, since the card doesn't have a dedicated remove helper like _01063 does.

Also fixed **_01067 (Jean Urbain)** — grants `Technique_PlusOneRiposte` (as `Technique_01067`) to Musketeers at his location. Same inline removal pattern as _02022.
