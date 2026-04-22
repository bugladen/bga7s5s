## Action_02035 — Renown gate (Eddie)

**Requirement:** Scoundrel action not offered if there is no Renown at the pressured location to collect.

**Implementation:** `getEligiblePerformers()` filters opposed Scoundrels to those where `getCityLocation($character->Location)->Renown > 0`. Used by both `isAvailableToPlayer` and `getPerformersForAction`.

**WHY:** Matches “collect a Renown from that location” — no point showing the action when success would not move Renown. Did not add a hard throw in `EventActionTriggered` if Renown disappears between UI and click (race); availability + existing success-branch `getRenownForLocation` check remain.
