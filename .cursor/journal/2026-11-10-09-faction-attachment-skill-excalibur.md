# create-faction-attachment skill — Dietrich Blade learnings

Updated skill after finishing `_05Excalibur`. Captured WHYs that would otherwise regress:

1. **"Equipped performer issues a challenge"** ≠ Stiletto "challenge at this location" — gate `challengerId == owningCharacter->Id`.
2. **"Target opposing … with equal or greater [Combat]"** ≠ lock to challenge defender. Pick any opposing at performer location with `ModifiedCombat >= performer`. Offer when ≥1 qualifies (weak defender + strong bystander). Sniper `wound-{id}`; stash PerformerId only.
3. **CAD free +1 Riposte** → own plain-string Technique class — `PlusOneRiposte` + `setId` keeps translate wrappers (Robin precedent).

Touched: SKILL.md shape + compose blurb + description triggers; pattern-d.md subsection; references.md; compose.md; checklist 21f; wiring.md.
