# Cards with techniques mentioning "adversary"

User asked for cards whose technique Name/text mentions "adversary" (not just `$adversary` code vars).

Filtered on `clienttranslate(...adversary...)` in technique Names. 24 unique cards (02026 has two such techniques: a+b).

Full list returned to user by card ID + name.

## Follow-up: IN_DUEL gate nuance (Eddie)

Do **not** treat "Technique text contains adversary ⇒ always gate IN_DUEL".

Correct rule (cost • effect):
- **"adversary" in the cost / condition before the •** (or equivalent "If …," preface) → must gate `Game::IN_DUEL` in `isAvailableToPlayer` because availability reads duel-opponent state.
- **"adversary" only in the effect after the •** → do not add `IN_DUEL` solely for that word; duel menu already scopes Techniques.

Documented in:
- `.claude/skills/create-character/pattern-e.md` (In-duel availability gate table)
- `.claude/skills/create-character/checklist.md` item 14
- `.claude/skills/create-character/SKILL.md` Technique shape row
- `.claude/skills/create-faction-attachment/pattern-e.md` + checklist

WHY: prior over-reading of Soline/"adversary = duel opponent" pushed agents to blanket-gate every Technique that named the adversary, including pure effect lines like Burnished Cuirass / Syrneth Hand.

## Follow-up: Gambling Techniques always IN_DUEL

Eddie: Gambling Technique must be gated to be in a duel. Updated Pattern E / checklists so Gambling always requires **both** `IN_DUEL` and `DUEL_GAMBLED` (not `DUEL_GAMBLED` alone). Independent of the adversary cost/effect split.
