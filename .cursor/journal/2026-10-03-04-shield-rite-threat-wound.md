# Shield Rite Reaction_04058 — leftover threat wrongly triggers

## Bug

En Garde Sorcerer Reaction offered during duel when performer took wounds from converted leftover threat. Print: "When an opponent's ability would wound your performer" — threat conversion is not an ability.

## Why it fired

`stDuelEndOfRound` does:
`createCharacterBeingWoundedEvent($actor->Id, $adversary->Id, $wounds, $reason)` — no abilityId (defaults `''`).

`isOpponentAbility` only checked source card ControllerId. Adversary character is opponent-controlled → true even with empty abilityId.

Same trap Kaspar journal (2026-05-25-02) already documented for `_03014`. Cascade `02059` and Leather Spaulders `04053` already gate `abilityId === ''`.

## Fix

Early return false in `Reaction_04058::isOpponentAbility` when `$abilityId === ''`. WHY not also require getAbilityById: second branch resolves in-play actions by abilityId when source card missing; empty-id gate is enough and matches Kaspar's filter.

## Latent sibling

Altruistic `Reaction_03031` copies the same ControllerId-only `isOpponentAbility` — leftover threat on a friendly character with another performer at location would also wrongly offer redirect. Not fixed this session (user asked Shield Rite only). Same one-liner if reported.

## Unfinished

None for 04058. Consider skill checklist D.4 note: opponent-ability wound gates must reject empty abilityId (threat/challenge convert).
