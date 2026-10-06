# Justice Served Cold (_02049) — challenge target UI

## Bug

Justice Served Cold City Action showed every opposing character at the performer's location as selectable (e.g. Odette). Card text restricts to **Mercenary** or **Thug**.

## Root cause

`Action_02049::isValidTargetForAbility` already enforced traits (and availability used the same filter). Shared challenge state `highDramaChallengeActionChooseTarget` builds selectable ids in `ArgumentsTrait::argsHighDramaChallengeActionChooseTarget` as "any opposing controlled character at location" — it never consulted the Action's validation. JS only highlights `args.ids`.

Server-side `actHighDramaChallengeActionTargetChosen` did call `isValidTargetForAbility`, but players still saw illegal targets highlighted (and could click them — error or confusion depending on path).

## Fix

In `argsHighDramaChallengeActionChooseTarget`, after the opposing-at-location filter, when `CHALLENGE_TYPE != DEFENDING_HONOR` and `CHOSEN_ACTION` resolves to an `IAbilityThatTargetsCharacters`, filter `$charactersAtLocation` through `isValidTargetForAbility`. Same Defending Honor exception as the framework act method (step 2 is friendly defender pick, not ability target).

Tightened `actHighDramaChallengeActionTargetChosen` to only call validation when `$action instanceof IAbilityThatTargetsCharacters` (avoids calling a method that doesn't exist on odd actions).

## WHY framework-level

Any challenge Action with extra target rules (02028 Influence ≥1, 02049 traits, etc.) shares this state — one fix helps all of them without per-card JS.
