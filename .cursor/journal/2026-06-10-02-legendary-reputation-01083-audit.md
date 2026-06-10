# Legendary Reputation (01083) Audit

## Card Text
> **City Action:** Your performer issues a [Combat] challenge to target opposing character. Only Leaders can intervene.

## What I Found

Two server-side gaps. Neither was exploitable through the normal UI (the args function and the action button enablement pre-filter), but both are out of step with the canonical challenge-action pattern in `Action_01056` (Move Along) and `Action_01131` (Iron and Velvet).

### Gap 1: Missing `getPerformersForAction` override

`RiskCityAction::getPerformersForAction` returns *all* of the player's city characters — no `canChallenge()` filter, no "performer must have an opposing character at its location" filter. The original `isAvailableToPlayer` correctly checked both predicates at the action level, but the per-performer filter happened nowhere.

In practice, this meant the performer-chooser state could offer a city character that has no opposing characters at its location, or one that can't challenge (e.g., `_01178` after engaging + ability-used). Picking such a performer would dead-end the action: the target chooser would have zero valid targets.

Mirrored `Action_01056`'s pattern — `getPerformersForAction` is now the single source of truth for "who can perform this action," and `isAvailableToPlayer` defers to it via `count(...) > 0`. Less duplication than computing the same predicate twice with different shapes.

### Gap 2: `isValidTargetForAbility` accepted uncontrolled targets

The original check was `if ($character->ControllerId == $performer->ControllerId)`. An uncontrolled character (`ControllerId == 0`, e.g. an available Mercenary at the performer's location) would pass that check (`0 == 5` is false) and pass the same-location check, then fall through to a `[true, ""]` return.

The UI args function (`ArgumentsTrait::argsHighDramaChallengeActionChooseTarget`) already filters with `$character->ControllerId && $character->ControllerId != $playerId`, so this was server-side defense-in-depth only — a crafted API call could otherwise target a Mercenary as if it were an enemy. Same fix as the Guild Triskelion (01198) audit in 2026-04-08: layer an `isControlled()` guard onto the same-controller check.

### What's right

- `Risk` class: `implements IHasActions, IRiskThatTargetsCharacters`. Faction, CardNumber, WealthCost, combat stats, Traits all match the printed card. ✓
- `LEGENDARY_REPUTATION_CHALLENGE_TYPE` is set in `EventActionTriggered`, and `Theah::interventionCheck` enforces the "Only Leaders" restriction via `! $character instanceof Leader`. `Reaction_02058` (a card that intervenes externally) also respects the gate by filtering its valid performers to Leader instances. ✓
- `CHALLENGE_STAT = STAT_COMBAT` is set. ✓
- State wiring: `"01083" => HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` in `states.inc.php`. No custom sub-state needed — this is a vanilla "performer picks an opposing target" challenge. ✓
- Pre-commit hook: `createActionResolvedEvent` literal appears as the comment `// createActionResolvedEvent() is called when the challenge is resolved`. ✓ (resolution fires it from the challenge flow, not from this Action)
- `IAbilityThatTargetsCharacters` correctly applied on `Action_01083` (the action targets a character).
- The card text doesn't say "Engage your performer," so the standard `canChallenge()` filter (which is just `isControlled()`) is the right gate — no `! $p->Engaged` layered on top, unlike `Action_03021` (Cornered).

## What I considered and rejected

- **Adding `! $performer->Engaged` to the performer filter.** The card text doesn't impose an engage cost. The codebase's standard "performer issues a challenge" cards (01056, 01073) don't filter engaged performers either; that's `canChallenge()`'s job and the base implementation treats engagement as orthogonal. If there's a downstream rules question about engaged performers initiating challenges, that's a systemic issue (per the Iron and Velvet audit), not 01083's to solve.
- **Changing `getCharactersAtLocation` filter to `getOpposingCharactersAtLocation` helper.** Functionally equivalent here (the helper does the same `isNotControlledByPlayer` filter). Kept the manual filter to keep the diff minimal and to match the shape used in the existing pattern reference (Action_01056). A separate sweep could unify all challenge actions on the helper.

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01083.php` — added `getPerformersForAction` override; tightened `isValidTargetForAbility` to reject uncontrolled targets; collapsed `isAvailableToPlayer` to defer to `getPerformersForAction`.

## Risk Assessment
Low. The performer-filter fix is strictly more restrictive — it removes options that would have dead-ended anyway. The target-controller fix closes a server-side hole already covered by the UI args filter. No behavior change for legitimate UI play.
