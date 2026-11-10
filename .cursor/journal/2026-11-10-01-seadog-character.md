# _05Seadog (Mikael Smitski) — finish Character

## Prior context
Vulture finished yesterday. Seadog stub open with Text; CAD prototype (no clienttranslate).

## Classification
1. **While an opponent controls equal or more characters than you, their characters opposing Mikael have −1[Influence].** → Pattern A Giacinto location opposing Influence aura + Ren `_01121` equal-or-more character-count gate (per opposing controller). No Sorcerer trait filter — all opposing characters. Home empty. Fate's Silence blank/unblank. Dedicated condition + client notifs (Giacinto shape).
2. **Covert Reaction: When a character would move from this location • Cancel the movement.** → Pattern D cancel-and-reissue on `EventCardMoving` only (no engage). Covert = `cardInCity` + uncontrolled. Any character (not "your" / not opponent-effect). Decline → `cancelDeclinedByCardIds`. Mirror Jak-Sen move half minus opponent/your gates + Covert uncontrolled.

## WHY these mirrors
- Per-opponent count gate ("their characters") — opponent A outnumbers → only A's opposing chars debuffed; opponent B with fewer stays clean.
- Count can flip without anyone moving to/from Mikael's location → also resync on any muster/approach/recruit/destroy (exclude destroyed id from counts while still listed).
- Covert Reaction is not City Reaction Jak-Sen: no "your character", no "opponent's effect", sourceId==0 framework moves ARE legal triggers if Covert location holds.
- Prototype CAD: plain strings, no clienttranslate.
- clearDebuffsAtLocation clears by stamped condition (not current eligibility) so stale stamps cannot linger when Mikael leaves.

## Touched
- `_05Seadog.php` — IHasReactions + Influence aura handleEvent
- `reactions/Reaction_05Seadog.php` — Covert cancel-move
- `Game.php` / `FakeGame.php` / `seventhseacityoffivesails.js` / `Notifications.js` — `SEADOG_INFLUENCE_REDUCTION_CONDITION`
- `tests/regression/cad/Card_05Seadog_Test.php` (4) + `Reaction_05Seadog_Test.php` (2) — 6/6 PASS
- Skill: SKILL.md shape rows, pattern-a / pattern-d subsections, references.md, checklist 89a/89b

## Unfinished
None for this card.
