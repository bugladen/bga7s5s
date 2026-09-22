# Leather Spaulders `_04053`

## Context
Continuing BAS Neutral attachments after Motion to Delay `_04052`. Card is Armor; Text has equip restriction + engage Reaction to ignore an opponent-ability wound.

## Classification
1. "May only equip to your character with 2[Finesse] or more" → Pattern A (dual gate). Use `ModifiedFinesse >= 2` (same as Maneuver_02059 / Technique_01128 stat gates — printed [Finesse] means current modified value).
2. Reaction: opponent ability would wound equipped character, engage this card → Ignore → Pattern D AttachmentReaction.

## Mirror choice / WHY
Semantic twin is Cascade `_02059` Reaction (Risk, wealth-pay ignore wound). Same cancel-first `EventCharacterBeingWounded` + clone/re-queue on decline + opponent-ability detection via `abilityId` + `getAbilityById` + ability owner ControllerId.

Differences vs 02059:
- AttachmentReaction + `ownerIsAttached` + target = equipped character only (not any controlled character)
- Cost = engage this card (gate `!Engaged`), not hand wealth pay / `EventRiskReactionTriggered`
- No GameState

Attachment cancel-wound shape also in `Reaction_01181` (Sorte Deck) — cancel + clone + skipNextEvent on pass — but that heals rather than ignores. Combine 02059 gates with 01181/04026 AttachmentReaction engage plumbing.

## Not doing
- No Technique/Maneuver
- No HIGH_PRIORITY needed: wound is canceled immediately like 02059 (no queued Resolve race)

## Shipped
- `_04053.php`: IHasReactions + ReactionTrait; Pattern A dual gate on `ModifiedFinesse >= 2`
- `reactions/Reaction_04053.php`: AttachmentReaction cancel-first wound ignore; engage cost; opponent-ability gate from 02059; equipped-character-only target

## Feel
Straightforward compose of A + D. Closest semantic twin is a Risk (`02059`) so the engage-cost swap is the main adaptation — same cancel/re-queue bones as Sorte Deck `01181`. No states/JS. Second simultaneous wound while offer pending still slips through (`savedWoundEvent !== null` early-out) — same as 02059; not inventing a queue.

## Skill update (same session)
Folded into `create-faction-attachment`:
- Pattern A subsection: stat-threshold equip (`Modified* >= N`)
- Pattern D subsection: ignore-wound (Cascade→attachment engage swap; no HIGH_PRIORITY; equipped-host-only)
- Shape table rows + description triggers + Neutral exemplar + compose
- references (`_04053`, Cascade `02059`), checklist 3c/21d, helpers/wiring footguns
