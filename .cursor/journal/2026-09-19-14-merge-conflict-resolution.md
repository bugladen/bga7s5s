# Merge conflict resolution (bas ← origin)

## Context

Mid-merge on `bas` after pulling `origin`. Three unmerged paths.

## Resolutions (WHY)

### `states.inc.php` — HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS

Both sides added deferred challenge-technique transitions after Accept/Intervene. Kept **all** keys:

- From origin: `01090`, `02026a`, `02026b` (Lorenzo / Croc de Lion)
- From HEAD: `04002_3` (Danilo intervene wound-or-draw), `04017` (Jägerarmbrust adversary discard)

WHY union not pick-one: each transition is a different card's deferred picker; dropping either side would soft-lock that card when its `createTransitionEvent` fires.

### `.claude/skills/create-character/checklist.md` items 12–14

- Kept HEAD's fuller Pattern F item 12 (Danilo intervene-follow-up + Engage-printed note) and Giacinto `04032_2` note on item 13.
- Took origin's Pattern E item 14 wording (Gambling always `IN_DUEL`+`DUEL_GAMBLED`; `IN_DUEL` only when adversary is in the cost before the •).

WHY: HEAD had more challenge-integration traps; origin had the sharper adversary-cost vs effect-side `IN_DUEL` rule that matches pattern-e.md.

### `.claude/skills/create-faction-attachment/SKILL.md` shape table

Kept HEAD's extra Pattern E rows (engage +Thrust/+Parry, trait Resolve-time gate, adversary discard picker, EndOfRound threat). Updated Gambling row to origin's **both** `IN_DUEL` and `DUEL_GAMBLED` gate (and kept `_04016` ref from HEAD).

WHY: origin only improved one cell; HEAD had the rest of the FAF attachment Technique shapes. Dropping HEAD rows would lose scaffolding guidance for `_04017` / `_04016` / `_04026`.

## Status

Conflicts staged; merge not committed (user did not ask to commit).
