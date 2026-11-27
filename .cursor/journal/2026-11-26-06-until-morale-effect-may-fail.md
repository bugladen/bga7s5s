# Until Morale Improves — effects may fail (01152a/b)

## Context
User corrected commit `a00bbb9` (engage/en garde availability gate). Effects are
allowed to fail: pay wound cost, attempt Engage/En Garde, if already that state
the effect no-ops, action still spent. Useful for beneficial-on-wound characters.
Rejected Pass-button alternative — prefer allowing any same-location target.

## Change
- Dropped custom `isAvailableToPlayer` / `getPerformersForAction` gates on both
  actions — SchemeCityAction parent (any city performer) is enough.
- Args / `isValidTargetForAbility`: any character at performer location (no
  Engaged filter).
- Resolve: always wound; queue Engage only if `!$target->Engaged` (b); queue
  En Garde only if `$target->Engaged` (a).

## Also fixed: 01152a Engaged vs Engarded
Prior code queued `createCardEngagedEvent` for the En Garde action — noop on
already-Engaged targets, and would Engage an en-garde target. Switched to
`createCardEngagedEvent`. Tests previously asserted the wrong event type.

## WHY not Pass
Empty-target Pass would still need a chooser escape when the only characters
are "wrong state". Allowing wrong-state targets + skip effect is simpler UX
and matches the rules clarification directly.
