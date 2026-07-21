# Skill update — create-character after Yepikhodov _03051

## Why
Folded session learnings into `.claude/skills/create-character/SKILL.md` so the next FAF character with a location Technique aura / Leader-move / copy-from-ally-attachment doesn't re-derive the traps.

## Key WHYs captured in the skill
1. **En Garde ≠ Engage** — `createCardEngardedEvent` vs `createCardEngagedEvent`; requiring Engaged attachments for "Then engarde" so the effect isn't a no-op.
2. **Grant aura** — Jean lifecycle; `setId` before `setOwnerId` for ClassId lookup; exclude aura source; accept recruit-without-CardMoved hole.
3. **Third-party copy** — Dame `02055` resolve, but **skip** `isAvailableToPlayer` on source techniques (actor is the ally, not Yepik). Identify Yepik by ExpansionName+CardNumber to avoid Technique↔card circular require.

## Files
- Skill: `.claude/skills/create-character/SKILL.md`
- Results: `_results/2026-07-16-create-character-skill-03051.md`
- Prior card journal unchanged: `2026-07-15-04-yepikhodov-03051.md`
