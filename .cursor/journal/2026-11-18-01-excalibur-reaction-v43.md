# _05Excalibur Reaction PTv0.4.3

## Context

Prior sessions finished Dietrich Blade Reaction with equal-or-greater Combat gate + Knight trait. New art `05Excalibur.v4.3.jpg` printed Reaction without the Combat filter.

## Printed change

Old: wound performer + target opposing character with equal or greater [Combat].
New: `When the equipped performer issues a challenge, wound them • Wound target opposing character.`

Card `Text` already matched v4.3; Reaction class / tests / skill docs still had the Combat gate.

## Code

- `Reaction_05Excalibur`: dropped `ModifiedCombat >= performer` filter. Still location-scoped via `getOpposingCharactersAtLocation` (challenge context; not board-wide). Offer when ≥1 opposing at location. Updated Name / description / UserException.
- `_05Excalibur`: Image → `05Excalibur.v4.3.jpg`.
- Tests: weaker opposing now offers; removed Combat-gate negative; buttons include weak; no-op when location empty of opposing.
- Skill docs (pattern-d / SKILL / wiring / checklist / references / compose): removed Combat-gate language, noted PTv0.4.3 so a future agent does not "restore" it from old journal.

## WHY keep location scope

Printed text does not say "at this location", but challenge Reactions that pick "opposing character" without a travel verb have always been location-scoped here (old Excalibur + Sniper-style pickers). Board-wide would be a large silent power bump; did not assume that from a Combat-filter deletion alone.

## Unfinished

None for this card. Upload new jpg to BGA asset store if not already.
