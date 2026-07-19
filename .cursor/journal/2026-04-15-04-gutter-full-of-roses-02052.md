# Gutter Full of Roses (_02052) - Neutral Scheme Implementation

## Card Ability
**Resolve:** Add a Renown to [The Grand Bazaar]. Then, move a Renown to [The Grand Bazaar].
**Forced:** When a player's adversary is destroyed during a duel at [The Grand Bazaar] - That player collects a Renown from [The Grand Bazaar]. (This Forced ability activates for any player.)

## Implementation

### Files Changed/Created
- `_02052.php` — Full card class with handleEvent, argsFromCard, actFromCardWithIds, actFromCardPass
- `State_planningPhaseResolveSchemes02052.php` (new) — State for choosing source location for move
- `States.php` — Added `PLANNING_PHASE_RESOLVE_SCHEMES_02052 = 2602052`
- `states.inc.php` — Added `"02052"` transition mapping
- `OnEnteringState.tac.js` — Makes non-Bazaar locations with Renown > 0 selectable
- `OnUpdateActionButtons.tac.js` — Confirm Location + Pass buttons
- `OnLeavingState.tac.js` — resetCityLocations cleanup

### Design Decisions

**Resolve effect - two-part:** The add is automatic. The "move" requires player choice of source location (any non-Bazaar location with Renown > 0). Followed _01150 (Parley Gone Wrong) and _02025 (Tea and Cakes) patterns. WHY: "Move a Renown to X" means take from somewhere else and put at X - the source must be chosen.

**Always enter move state with pass button:** Initially tried to skip the state if no locations had Renown. User corrected this - always enter the state and show a Pass button when no valid locations exist. WHY: Consistency with other cards that show pass, and avoids a race condition where the Renown check at resolve time may not reflect the actual state when the add-Renown event processes (though since we're adding to Bazaar which is excluded from sources, this was unlikely to matter).

**Forced ability - any player:** Unlike _02024 (Oath of Vengeance) which checks `$this->ControllerId`, this forced ability fires for ANY player. The card text explicitly says "(This Forced ability activates for any player.)" So we don't filter by controller in the character destruction handler.

**Adversary logic:** The destroyed character IS the adversary of the other participant. If challengerId is destroyed, the defender's adversary was destroyed, so defender collects. Guard clause for destroyed character not being a duel participant (shouldn't happen, but defensive).

**Location check via challenger:** Using `$challenger->Location` for duel location. Both duel participants should be at the same location.

**JS pattern:** OnEnteringState filters locations exactly like _01150 does for Forum - excludes `bazaar-image` and locations with `reknown === 0`. OnUpdateActionButtons has both Confirm Location and Pass buttons (Pass in alert color).

### Patterns Used
- _02004 (Crash the Party) — basic scheme resolve with Renown add
- _01150 (Parley Gone Wrong) — move Renown with location selection + actFromCardWithLocations + Pass + JS filtering
- _02024 (Oath of Vengeance) — forced ability on EventCharacterDestroyed during duel
- _02025 (Tea and Cakes) — State class pattern with actFromCardWithLocations
