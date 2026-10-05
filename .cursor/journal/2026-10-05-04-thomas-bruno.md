# _05Thomas Bruno Ruffian (CAD Brute)

## Context inherited
Coleman/Stefano and Henry/Carlo finished earlier today — Penya intervene ban + hand→play ban + Duelist Maneuver self-muster. Thomas/Bruno is the same shape with **+1 Parry** before muster (not wound).

## Classification
1. **Bruno cannot intervene** → Penya hard ban
2. **Cannot enter play from hand except during a duel** → `canBePlayedAsBruteFromHand=false` + muster eventCheck (HAND + !IN_DUEL)
3. **Duelist Maneuver: +1[Parry]. Put Bruno into play at this location** → Pattern E: Coleman gates + Calculate Parry (PlusOneParry / 04046) + Resolve muster (Coleman)

## WHY / plan
- Mirror `_05Coleman` for bans + Duelist/dueling-line availability.
- Image stub said `05Bruno.v3.jpg` but asset is `05Thomas.v3.jpg` (same designer-name image pattern as Coleman/Henry). Fix Image.
- +1 Parry on `EventDuelCalculateManeuverValues` (generic PlusOneParry shape); muster on `EventResolveManeuver` (Coleman). Both in one Maneuver class — Calculate and Resolve are separate duel pipeline events so no linking flag needed.
- CardNumber keep stub 5. WealthCost 0. Recruit already in TraitNames.
- No states/JS.

## Done
- `_05Thomas.php` — Brute + intervene/hand bans + Maneuver wiring; Image → `05Thomas.v3.jpg`
- `maneuvers/Maneuver_05Thomas.php` — Duelist + dueling-line; Calculate +1 Parry; Resolve muster
- Skill: SKILL row, pattern-e subsection, checklist 107, references

## Watch
- Same Coleman playtest note: `cardMustered` from dueling line UI cleanup
- Confirm Parry still applies if muster destroys/moves the combat card mid-pipeline (Calculate usually runs before Resolve for activated Maneuvers — should be fine)
