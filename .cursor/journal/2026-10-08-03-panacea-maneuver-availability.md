# Panacea Maneuver_04038 availability

## Bug
Usable gate required `$actor->Engaged || $actor->Wounds > 0`. Academic already en garde with 0 wounds could not play the Maneuver.

## Correction (user)
Printed: **Academic Maneuver:** En garde your participant. They heal a wound.
No cost before a • — only Academic is a precondition. Effects may noop; card still playable (combat-card discard / Riposte×1 etc. still matter).

## WHY not keep "complete as much as possible" as an availability filter
That rule is about *resolving* multi-half effects (skip impossible halves). It is not "grey the ability when both halves are currently noops." Availability greying is for costs / trait headings / targeting pools.

City Action (`Action_04038`) still filters targets to Engaged|wounded — different: Cesca chooser needs a legal target. Left alone; user only reported Maneuver.

## Change
`Maneuver_04038::isAvailableToPlayer` → parent + Academic actor. Resolve still only engardes if Engaged / heals if Wounds > 0.
Updated create-risk checklist item 63 + references `_04038` row so future agents don't reintroduce the hide-when-noop gate.
