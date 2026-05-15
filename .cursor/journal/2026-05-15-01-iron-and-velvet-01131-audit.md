# Iron and Velvet (01131) Audit

## Card Text
> **City Action:** Your unequipped performer issues a [Combat] challenge to target opposing character.
>
> **Maneuver:** Wound each participant equipped with an attachment.

## Bugs Found and Fixed

### Bug 1: Target attachment check was wrong (the real bug)

`Action_01131::isValidTargetForAbility` was rejecting target characters that had attachments:

```php
if (count($character->Attachments) > 0)
{
    return [false, $game->translate("Character has attachments")];
}
```

The card text restricts the **performer** to being unequipped, not the target. The target can be any opposing character at the performer's location. Removed the check.

This is the kind of mistake that's easy to make when reading the card text quickly: "unequipped" sits next to "performer," and a tired implementer can pattern-match it onto the target by mistake — especially when the performer filter already (correctly) filters by `count($performer->Attachments) == 0`. WHY note for future-me: if you see this kind of symmetric-looking restriction on both ends and the card text only mentions one end, the duplicated end is probably the bug.

### Bug 2: Missing canChallenge() on performer filter

Action_01131 filtered performers by `count($performer->Attachments) == 0` but did not call `$performer->canChallenge()`. Every other "performer issues a challenge" action in the codebase (01036, 01056, 01071, 01083) calls `canChallenge()` on the performer.

This matters for cards like `_01178` which override `canChallenge()` to return false when engaged AND ability-used. Without the check, an exhausted 01178 could be selected as the performer for Iron and Velvet's challenge, then fail (or worse, slip through) downstream. Added `$performer->canChallenge()` to both `isAvailableToPlayer` and `getPerformersForAction`.

## Things That Look Right

- **Maneuver_01131**: Wounds each duel-round participant (actor + opponent) that has attachments. Matches "Wound each participant equipped with an attachment." Standard pattern used by many other maneuvers (01055, 01057, 01084, 01108a, 01110, 01113, 01114, 01165). ✓
- **Performer filter — unequipped**: `count($performer->Attachments) == 0` is correct enforcement of "unequipped" on the performer. ✓
- **Same-location check on target**: Required for any challenge — challenges happen at a location. ✓
- **Different-controller check on target**: Matches "opposing" (per memory: opposing = different controller AND same location). ✓
- **Action flow**: Sets `CHALLENGE_STAT=Combat` and `CHALLENGE_TYPE=IRON_AND_VELVET_CHALLENGE_TYPE`, queues transition '01131' and ActionResolvedEvent. Standard. ✓
- **Class hierarchy**: Extends `RiskAction` (not `RiskCityAction`) and implements its own `getPerformersForAction` using `getCharactersInCityWithOpposingCharacters` (a stricter filter that requires opposing characters exist at the performer's location). This is intentional and acceptable; `RiskCityAction` would do less. ✓
- **`IRiskThatTargetsCharacters`**: Marker interface only, no contract. ✓
- **`IAbilityThatTargetsCharacters`**: Correctly implemented (target validation matches "Text targets a character → must implement this interface", per memory). ✓

## What I Did NOT Change

- Did not switch `extends RiskAction` to `extends RiskCityAction` — the custom performer filter is more restrictive (city + opposing co-located) and removing the override would lose that restriction.
- Did not add an in-handle "challenge type engages performer" fix — per the Move Along audit (01056), this is a systemic question across multiple challenge types and not a single-card concern.

## Risk Assessment

Low. The target-attachment check was strictly more restrictive than the card text allows, so the fix opens up legal plays that were being incorrectly blocked. The `canChallenge()` addition is strictly more restrictive but in a defensive direction (avoids exhausted-but-still-selectable performers), matching the codebase pattern.
