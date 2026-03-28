# Lord Stranahan III (_02022) Audit

## Card Text
> When a challenge is issued to your **Diplomat** at this location, wound the challenging character.
> Your **Musketeers** at Stranahan's location gain "**Technique:** Gain Lethal."

## Files Audited
- `modules/php/cards/tac/_02022.php` (main class — Character)
- `modules/php/cards/techniques/Technique_GainLethal.php` (granted technique)
- `modules/php/cards/_7s5s/_01067.php` (reference card for grant/removal pattern)
- `modules/php/cards/_7s5s/techniques/Technique_01049.php` (reference for Gain Lethal technique pattern)
- `modules/php/cards/techniques/Technique_PlusOneRiposte.php` (reference for isAvailableToPlayer pattern)
- `modules/php/theah/events/EventResolveTechnique.php` (has comment clarifying correct event type)
- `modules/php/theah/events/EventDuelCalculateTechniqueValues.php` (correct event type)

## Challenge Issued to Diplomat — Ability 1 ✅
Handler for `EventChallengeIssued` correctly checks:
- Defender controlled by same player
- Defender at Stranahan's location
- Defender has "Diplomat" trait
- Wounds the challenger

No issues found.

## Musketeer Technique Grant/Removal — Ability 2 ✅
Compared against `_01067` (Jean Urbain) as the reference pattern.

### EventCharacterRecruited ✅
Adds `Technique_GainLethal` (with classId "Technique_02022") when a Musketeer is recruited at Stranahan's location. Checks: not self, same controller, same location, not PLAYER_HOME, has "Musketeer" trait, implements IHasTechniques.

### EventCardMoved — All Three Branches ✅
1. **Stranahan moves**: Removes technique from Musketeers at old location, adds to Musketeers at new location. Correctly checks `!= LOCATION_PLAYER_HOME`. ✅
2. **Musketeer moves to Stranahan's location**: Adds technique. ✅
3. **Musketeer moves from Stranahan's location**: Removes technique by `getTechniqueByClassId("Technique_02022")`. ✅

Pattern is a 1:1 match with `_01067`.

## Technique_GainLethal — Fixes Applied

### Fix 1: Missing `isAvailableToPlayer()` ❌ → FIXED ✅
`Technique_PlusOneRiposte`, `Technique_PlusOneParry`, and `Technique_01049` all override `isAvailableToPlayer()` to check `Game::IN_DUEL`. `Technique_GainLethal` was inheriting the base class default of `return true`, meaning it would appear as an available technique option outside of duels (which makes no sense for a combat-only ability).

Added the same `isAvailableToPlayer()` override pattern used by the other generic techniques.

### Fix 2: Wrong Event Type ❌ → FIXED ✅
Was listening for `EventResolveTechnique`. The comment in `EventResolveTechnique.php` line 5 explicitly says: "Use EventDuelCalculateTechniqueValues if you want to modify duel round stats."

The lethal flag IS a duel round stat — it modifies how threat converts to wounds. `Technique_01049` (also a "Gain Lethal") correctly uses `EventDuelCalculateTechniqueValues` for its lethal logic.

Changed to `EventDuelCalculateTechniqueValues` and switched from `getDuelRoundActor()` to `$event->actorId` (which is available on the calculate event).

**WHY this matters**: `EventResolveTechnique`'s hub handler only records the technique usage in the DB (duel_round_technique table). `EventDuelCalculateTechniqueValues`'s hub handler does the full combat stat calculation AND sends the notification with lethal text. Using the wrong event meant the lethal ThreatModified event was queued during the resolve phase instead of the calculate phase. While the ThreatModified would still eventually update the DB, it would arrive at a different point in the event processing pipeline than intended.

## Lethal Ternary Direction — Consistent with Codebase
The ternary pattern `$actorId == $challengerId ? null : true` is used consistently across ALL lethal implementations:
- `Technique_01049` (EventDuelCalculateTechniqueValues)
- `Maneuver_01057` (EventResolveManeuver)
- `Maneuver_01031` (actFromManeuverWithId)
- `Reaction_01127` (performReaction)

Left this pattern unchanged since it's the established convention across 5+ cards.

## Refactor: createGainLethalEvent Helper

Eddie suggested consolidating the repeated lethal ternary pattern into a helper. Added `EventFactory::createGainLethalEvent(int $actorId, Theah $theah)` which encapsulates the challenger/defender ID lookup and the ternary logic.

Updated all 5 call sites:
- `Technique_GainLethal.php` — `$event->actorId` from `EventDuelCalculateTechniqueValues`
- `Technique_01049.php` — `$event->actorId` from `EventDuelCalculateTechniqueValues`
- `Maneuver_01057.php` — `$actor->Id` from `getDuelRoundActor()`
- `Maneuver_01031.php` — `$actor->Id` from `getDuelRoundActor()`
- `Reaction_01127.php` — `$character->Id` (the owning character who is the actor)

**WHY this matters**: The ternary pattern was the same in all 5 places but each had to independently look up challenger/defender IDs and compute the null/true values. If the direction ever needs fixing (see note about the ternary direction below), it's now a single-line fix in `EventFactory` rather than hunting down 5 scattered call sites.

## Known Limitation (Not Fixed, Same as _01067)
When Stranahan is destroyed/discarded from play, Musketeers at his former location keep the technique. This is the same codebase-wide gap documented in the Jean Urbain audit — `EventCharacterDestroyed`/`EventCardDiscardedFromPlay` don't fire `EventCardMoved`, so the grant/removal handler doesn't trigger.

Similarly, when Stranahan himself is recruited at a location with existing player-owned Musketeers, those Musketeers won't get the technique until a move event fires. Same gap as `_01067`.
