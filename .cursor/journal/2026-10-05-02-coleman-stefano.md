# _05Coleman Stefano Scofflaw (CAD Brute)

## Context inherited
Prior session finished Cooper/Vissenta (ignore-wound Reaction + Thug Technique aura + wound-Red-Hand Technique). Coleman was an unfinished Brute stub.

## Classification
Printed Text → three shapes:
1. **Stefano cannot intervene** → Penya hard ban (`canIntervene` + `eventCheck` on `EventCharacterIntervened`)
2. **Cannot enter play from hand except during a duel** → `canBePlayedAsBruteFromHand()=false` + muster `eventCheck` (HAND + !IN_DUEL)
3. **Duelist Maneuver: Put Stefano into play at this location** → Pattern E pure-resolve Maneuver on the character

## WHY decisions
- **Extends Brute** (stub) — keep; Brute dusk trait + EventHub Brute muster path matter.
- **Play Brute always blocked** via new `Character::canBePlayedAsBruteFromHand`. Play Brute is HD-only; the printed "except during a duel" is the Maneuver / during-duel hand effects, not the Brute menu. Filtering both `getBrutesAvailableToPlayer` and `playerHasBrutes` so the button disappears when Stefano is the only Brute.
- **eventCheck uses `$this->Location == HAND`**, not `fromLocation`. fromLocation is filled inside EventHub *after* queue-time eventCheck. Maneuver musters from DUELING_LINE so it passes. During-duel Vittoria-style hand musters stay legal.
- **Maneuver on the Character** (combat card), not a Risk — same IHasManeuvers shape as attachment Maneuvers. "This location" = `$actor->Location`.
- **EventHub Brute cardRemovedFromHand gated on fromLocation==HAND.** Without this, dueling-line muster would double-decrement hand count (combat-card announce already removed him from hand).
- **CardNumber kept at stub 3** (CAD sequence after Dabney/Cooper). Printed CAD-10 is physical set id; class id is `_05Coleman`.
- **WealthCost = 1** from coin icon (stub omitted it — Angelo/Alcee set it explicitly).
- **Recruit** novel trait → TraitNames alphabetically after Redeemed.
- No states/JS — pure resolve Maneuver.

## Files
- `cards/cad/_05Coleman.php`
- `cards/cad/maneuvers/Maneuver_05Coleman.php`
- `Character.php` — `canBePlayedAsBruteFromHand`
- `Theah.php` — Brute picker filters
- `EventHub.php` — Brute hand-notify gate
- `TraitNames.php` — Recruit
- create-character skill: SKILL shape rows, pattern-e subsection, checklist 106–107, references

## Unfinished / watch
- Client: does `cardMustered` from dueling line cleanly update duel-line UI, or do we need a dedicated remove-from-dueling-line notify? Improvised Weapon equip path may be the closest mirror — watch in playtest.
- If another card musters Stefano from discard (not hand) outside a duel, eventCheck does not block — text only names "from hand."
