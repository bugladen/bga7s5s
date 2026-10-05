# _05Fedora Ariella Crook (CAD Brute)

## Context inherited
Coleman/Stefano, Henry/Carlo, Thomas/Bruno finished earlier today — Penya intervene ban + hand→play ban + Duelist Maneuver self-muster variants (plain / wound / +1 Parry).

## Classification
1. **Ariella cannot intervene** → Penya hard ban
2. **Cannot enter play from hand except during a duel** → `canBePlayedAsBruteFromHand=false` + muster eventCheck (HAND + !IN_DUEL)
3. **Duelist Maneuver: Discard all of your participant's threat. Put Ariella into play at this location** → Pattern E: Coleman gates + Resolve threat wipe then muster

## WHY / plan
- Mirror `_05Coleman` for bans + Duelist/dueling-line availability.
- Threat wipe is Resolve-only (not Calculate) — same pipeline as Henry wound+muster.
- "Your participant" = duel side whose ControllerId matches Ariella's controller (Axelle/Andare map). NOT both sides (Porté `_01085` zeros both — wrong here).
- Amount = `−getCurrentDuelThreat(participantId)` (Porté full-discard amount helper). Skip event when threat already 0 to avoid no-op notify noise.
- Do NOT gate availability on threat > 0 — muster still useful with empty threat (same as Henry not gating on adversary alive).
- Printed L→R: queue threat discard before muster.
- Image stub `05Fedora.v3.jpg` matches designer-name pattern (Coleman/Henry/Thomas) — keep.
- CardNumber 6, WealthCost 2, DashedInfluence + DashedParry already correct on stub. Recruit already in TraitNames.
- No states/JS.

## Done
- `_05Fedora.php` — Brute + intervene/hand bans + Maneuver wiring
- `maneuvers/Maneuver_05Fedora.php` — Duelist + dueling-line; Resolve threat discard then muster
- Skill: SKILL row, pattern-e subsection, checklist 107, references

## Watch
- Same Coleman playtest note: `cardMustered` from dueling line UI cleanup
- Confirm ControllerId map is correct if somehow a third party activated the Maneuver (shouldn't happen — combat-card Maneuver is for the card's controller during their Duelist actor's round)
