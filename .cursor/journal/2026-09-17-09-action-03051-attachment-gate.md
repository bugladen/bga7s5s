# Action_03051 — no-cost L→R if applicable (Eddie correction)

## Correction

Eddie: action must remain available if (1) no attachment, (2) attachment already En Garde. No cost before the `•`, so effects apply left-to-right **if applicable**.

Previous implementation (and create-character Pattern C / SKILL / checklist) wrongly gated availability on ≥1 Engaged attachment at destination. That treated "Then, en garde" as a prerequisite / cost. Wrong.

## Fix

`Action_03051`:
- `isAvailableToPlayer`: city + Leader exists + not already at Leader location. **No** attachment count.
- `handleEvent`: always queue move (`engage=false`). If engardeable Engaged attachments exist → transition to picker; else → `createActionResolvedEvent` (02007 / 02040 shape).
- Picker still only lists Engaged non-Fake candidates (En Garde only applies when something is spent).

## Docs updated so we don't re-break this

- `.claude/skills/create-character/pattern-c.md` (Move to Leader section)
- `SKILL.md` ability-shape row
- `checklist.md` item 60
- `references.md` Action_03051 / _03051 rows

## WHY (keep this)

Text before `•` = cost. Nothing before `•` = no cost. Semicolon / "Then," clauses after are effects applied in order when legal targets exist — not availability gates. Siesta `_02040` (heal only if Wounds>0) and `_02007` (discard chooser only if city cards) are the siblings.
