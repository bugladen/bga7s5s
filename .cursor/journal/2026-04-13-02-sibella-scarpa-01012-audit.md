# Sibella Scarpa (01012) Audit

## Card Overview
- **Name:** Sibella Scarpa — Deceitful Witch
- **Faction:** Vodacce
- **Stats:** Resolve 4, Combat 2, Finesse 2, Influence 3
- **Traits:** Sorcerer, Strega, Red Hand, Vodacce
- **Text:** "Sorcerer City Action: Wound Sibella - Wound target opposing character."

## Bug Found

### Missing opposing check in `isValidTargetForAbility`
The server-side validation in `Action_01012::isValidTargetForAbility()` only checked that the target was at the same location as Sibella, but did **not** check that the target was controlled by an opponent. The UI correctly limited choices to opposing characters (via `getOpposingCharactersAtLocation`), but the backend validation was incomplete.

Compared against Action_01015 (The Great Game) which has the same pattern — targets opposing characters — and correctly validates `$character->ControllerId == $performer->ControllerId` before the location check.

**Fix:** Added the `ControllerId` check before the location check, consistent with Action_01015's pattern.

## What Looked Good
- Sorcerer trait check and city check both correct
- Wounds Sibella as cost, then wounds target as effect — correct order
- All required events present (SorcererAbilityStart, SorcererAbilityPlayed, ActionResolved)
- `setUsed()` called correctly
- Frontend correctly highlights Sibella and makes opposing characters selectable

## Pattern Note
This is the same category of bug seen across multiple audits: the UI restricts choices correctly, but server-side validation is too permissive. Always check that `isValidTargetForAbility` enforces the same constraints as `getArgsFromAction`.
