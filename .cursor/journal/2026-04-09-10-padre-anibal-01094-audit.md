# "Padre" Aníbal (01094) Audit

Clean audit — no bugs. Card has two abilities:

1. **Passive +2 Influence during pressures** — `getInfluencePressureValue` override adds +2 when location Renown ≤ 1. Same pattern as `_01184` (Claude's +1 inf). Only fires during Influence-stat pressures, which is correct since the bonus is specifically `[Influence]`.

2. **City Action: En garde self** — `Action_01094` gates on `Engaged == true` (can't en garde if already en garded) and `Renown == 0`. Effect fires `EventCardEngarded` which sets `Engaged = false`.

No `cardInCity` check but `Engaged` acts as an implicit guard. Consistent with other self-engarde patterns in the codebase. Not worth flagging as a bug since it can't cause issues in practice.

Straightforward card. Nothing surprising.
