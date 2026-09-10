# Merge conflicts: main → bas

Resolving in-progress merge on `bas` (HEAD Mysta reaction) of `origin`/`main` (Wounds stat). Three files conflicted.

## Character.php — keep BOTH methods
- HEAD: `abilitiesAreBlanked()` (Fate's Silence `_04008`)
- incoming: `canBeWoundedByOpponentAbilities()` (Kaspar `_03014` default true; override returns false)

WHY both: independent predicates, both called from live code (`Card`/`Theah`/`Maneuver`/`Technique` vs `Action_03001`/`Reaction_03001`). Taking one side would break the other feature.

## create-risk checklist/helpers — Pattern A.2 Pass wording
HEAD said "Mandatory … Pass is forbidden". Incoming said "Optional … Pass is allowed".

WHY take incoming Pass language: framework code is authoritative —
- `FrameworkActionsTrait::actHighDramaPass` comment: locks *who*, not *whether*
- `Action_03032` sets EXTRA_ACTION_PERFORMER with "Pass allowed" / "may perform"

Also kept HEAD-only bits that weren't wrong:
- checklist §23 FakeAttachment Forced exception (Pattern B.2)
- helpers: `getAdjacentCityLocations` + `setPlayersMultiactive` bullets

## Status
Conflicts resolved + staged. Merge commit NOT made — user only asked to fix conflicts.
