# create-character skill update — Sanjay (_03037) learnings

## Why

Eddie asked to capture session learnings from implementing `_03037` into create-character. The critical correction: Pattern F previously treated "no Engage printed" as Don Constanzo conditional-engage only. Sanjay never engages — copying Don's pattern was wrong. Future agents need the trichotomy explicit.

## What was added

1. **Canonical ref** `_03037` Sanjay (Daring Tomcat)
2. **Ability-shape table** — gambled combat-card row; Collect Renown row; challenge-refused row expanded (Reaction vs passive); Issue-challenge row notes trichotomy; hand-size filter row
3. **Pattern A** — "Gambled combat-card stat bonus" subsection (`$event->gambled` vs `DUEL_GAMBLED`)
4. **Pattern F** — engagement section rewritten as trichotomy table (Engage printed / conditional engage / never engages); Examples row for `Action_03037`; "Adding a NEW challenge type" now includes avoid-NORMAL-auto-engage as justification
5. **Phase events table** — `EventDuelCalculateCombatCardStats` notes `$event->gambled`
6. **Reference table** — `_03037`, `Action_03037`, `Reaction_03037`
7. **When You Finish** — item 12 rewritten for trichotomy + minimal type wiring; items 39–40 (gambled passive, Collect Renown); item 35 journal list includes Sanjay

## Feelings

The engagement trichotomy is the whole value of this update. Without Eddie's correction, the next "no Engage printed" challenge action would get conditional engage again and silently engage someone who shouldn't. Dedicated challenge type with zero intervene restrictions feels weird until you realize NORMAL auto-engages — that's the WHY to preserve.
