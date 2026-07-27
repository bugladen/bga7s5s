# create-city-character skill update from Tijani _04cd29

## Why
Folded Tijani implementation learnings so the next En Garde City Action / Gambling Technique CityCharacter does not regress into "Engage as cost" or inventing "opposing".

## What changed
- **SKILL.md** shape table: En Garde City Action / En Garde Action row; Gambling Technique row; finish note on TraitNames + no invent opposing
- **pattern-c.md**: En Garde = precondition not Engage; adjacent engaged + Finesse If into eligibility; ControllerId ?: activePlayerId
- **pattern-e.md**: custom technique path; Gambling Technique gate; wound-on-lower-Finesse recipe
- **references.md**: `_04cd29` / Action / Technique / State rows; TraitNames.php path fix
- **checklist.md**: items 12–17 for En Garde, adjacent-engaged If-stat, Gambling, TraitNames; journal pointer
- **helpers.md**: do not invent opposing; adjacent City includeHome=false; DUEL_GAMBLED; TraitNames.php path
- **wiring.md**: techniques namespace note
- **pattern-g.md**: cross-link — En Garde Action is Pattern C, not pressure Influence

## Not changed
- Pattern G still owns italic *En Garde* pressure Influence — deliberately separate from En Garde Action label
- Did not duplicate full create-character Gambling Technique essay — pointer + CityCharacter-specific wound recipe only
