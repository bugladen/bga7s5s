# Katain DeWinter (_02011) Audit

## Context

Continuing the card audit series. Audited _02011 (Katain DeWinter) — an Eisen character with a Reaction that copies Ranged abilities and a Technique that engages a Ranged Weapon for +1 parry.

## Bug Found & Fixed: Technique missing "Weapon" trait check

The Technique text says "Ranged Weapon" but the code only checked `hasTrait("Ranged")`. This let Flourish/Ranged cards (Last Word, Precision) qualify for engagement even though they're not weapons.

Fixed in three spots in `Technique_02011.php`: `isAvailableToPlayer`, `getArgsFromTechnique`, and `actFromTechniqueWithId`. Each now checks both `hasTrait("Weapon")` and `hasTrait("Ranged")`.

WHY this matters: There are exactly two Ranged-but-not-Weapon cards in the current pool (Last Word _01055 and Precision _01057, both Flourishes). Without the Weapon check, Katain could engage a Flourish she has equipped, which contradicts the card text and could create weird game states if Flourishes have different engagement semantics than Weapons.

Interesting note: the companion code in Action_01055 and Maneuver_01055 (Last Word's own abilities) already correctly checks for both `hasTrait("Weapon") && hasTrait("Ranged")`, so the pattern exists elsewhere in the codebase — the Technique just missed it.

## Reaction: Clean

The Reaction implementation is thorough. All 8 `IRangedAbility` implementations are individually handled with hardcoded instantiation for each. This is a lookup-table approach — if new Ranged abilities are added, `Reaction_02011` will need manual updates. This is a known pattern in the codebase but worth noting for future expansion work.

## Observations

The Reaction design is interesting — it copies the ability and immediately triggers it, rather than just duplicating the effect inline. This means the copied ability goes through the full event pipeline (resolve events, calculate events, etc.), which is correct for abilities that have multi-step resolution flows.
