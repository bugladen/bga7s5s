# Térence Rois (_03028) Implementation

## Card

**Térence Rois — Pompous Perveyor (Montaigne, Diplomat/Merchant/Aristocrat)**
Resolve 4, Combat 0, Finesse 3, Influence 2.

Three abilities:
1. Cannot issue [Combat] challenges.
2. While participating in a duel at The Grand Bazaar, set Combat equal to Influence.
3. City Reaction: After a character equips an attachment at The Grand Bazaar • Draw a card.

## Decisions

### Combat challenge ban — eventCheck only, not canChallenge override

Wilhelm (`_02013`) uses `eventCheck` on `EventChallengeIssued` when `CHALLENGE_STAT == STAT_COMBAT`. Same pattern here inverted — Terence can still issue Finesse/Influence challenges via card actions, so `canChallenge()` stays at the default (`isControlled`). Basic Challenge always uses Combat; Terence will appear in that performer list but `eventCheck` blocks the issue. Considered adding `canIssueCombatChallenge()` on `Character` and filtering basic-challenge UI — skipped to keep the diff card-local; eventCheck is the engine backstop the skill requires.

### Duel Combat = Influence — flag + stored restore, not recompute-from-base

"Set as equal to" is a dynamic link, not a one-shot +N. `$DuelCombatEqualsInfluenceApplied` + `$CombatBeforeDuelOverride` mirror Ise's wounded-combat flag pattern (`_03016`) but restore to the stored pre-override value on `EventDuelEnd` instead of recomputing from base+attachments (attachments could change mid-duel; stored snapshot is safer).

Hooks:
- `EventDuelStarted` when participant at `Game::LOCATION_CITY_BAZAAR`
- `EventDuelEnd` clear
- `EventDefenderSwapped` / `EventChallengerSwapped` apply/clear when Terence enters/leaves the duel
- `EventCharacterInfluenceModified` re-sync combat while override active
- `EventCharacterCombatModified` re-sync if external source changed combat away from influence (EventHub applies stat before card handlers, so compare `NewCombat` vs `ModifiedInfluence`)

### City Reaction — single Reaction_03028, Draw/Pass

Button-based like `Reaction_01146a` / Odette pair. Triggers on `EventAttachmentEquipped` when equipping character is at Grand Bazaar, Terence is in city at Grand Bazaar, not fake attachment. Pass before `setUsed` preserves daily slot.

## Files

- Modified: `modules/php/cards/faf/_03028.php`
- Created: `modules/php/cards/faf/reactions/Reaction_03028.php`

No states/JS — standard button reaction.

## Skill update (2026-07-01)

Fed `_03028` learnings into `create-character` SKILL.md:
- Stat-specific challenge ban (`eventCheck` only, not `canChallenge` override) — table row + checklist #36
- "Set stat equal to stat" duel replacement pattern — new Pattern A subsection + checklist #37
- Third-party equip-at-location City Reaction — Pattern D subsection + checklist #38
- `_02013` Wilhelm as inverse reference for stat-specific challenge restrictions
- Event table rows for `EventAttachmentEquipped`, `EventCharacterCombatModified`/`InfluenceModified`
