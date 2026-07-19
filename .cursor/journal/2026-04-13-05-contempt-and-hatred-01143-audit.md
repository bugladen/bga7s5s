# Contempt and Hatred (01143) Audit

## Card Text

> Add a Renown to [The City Forum]. Then, you may add another Renown to any location. If you do, discard all City Cards there.
>
> All Mercenaries have -1 [Influence].
>
> **City Action:** Engage your performer - Pressure with [Influence]. You succeed even if tied. If successful, claim the location.

## Audit Findings

### Bug Fixed: Missing Mercenary trait check on EventCharacterRecruited

The `EventCharacterRecruited` handler in `_01143.php` was applying -1 Influence to ANY character recruited while the scheme was resolved, not just Mercenaries. The other two handlers (`EventResolveScheme` and `EventCardSentToLocker`) both correctly filtered with `hasTrait("Mercenary")` - only the recruited handler was missing it.

WHY this matters: Without the check, recruiting any non-Mercenary character (e.g., a Swordsman, Hero, etc.) while Contempt and Hatred is resolved would incorrectly reduce their Influence by 1. This is the same pattern of missing validation seen in previous audits today (01011, 01012, 01013).

### Everything Else Checks Out

- **Scheme Resolve**: Adds Renown to The Forums, then transitions to a state where the player MAY choose another location. The state has `actPass` and the UI shows a Pass button. If a location is chosen, Renown is added and all City Cards are discarded. Correct.
- **Mercenary Influence Modifier**: Applied on resolve, reversed on locker, and now correctly applied on recruit (with trait check). Covers all the lifecycle scenarios.
- **City Action**: Engages performer, pressures with Influence, sets CONTEMPT_AND_HATRED_PRESSURE_TYPE flag which is handled in UtilitiesTrait.php to make ties count as wins. If successful, claims location. Calls `setUsed()`. All correct.
- **Performer filtering**: Only unengaged characters are offered as performers. Correct per "Engage your performer" implying they must not already be engaged.
