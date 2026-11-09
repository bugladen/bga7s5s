# _05SnowWhite (Wildcall) v5 Renown destinations

## What changed
Printed Text (already in `$this->Text`) and art icons: **Add Renown to Docks + Forum** (Anchor + Temple). Resolve code still queued Forum + Bazaar from the Oct 14 stub.

Oct 14 journal note already flagged this: "Text says Forum+Bazaar (authoritative); image Anchor+Temple — if Eddie corrects destinations, swap Renown events only." Text was corrected; code wasn't.

## Code impact
Only `EventResolveScheme` destinations + notify string. Reaction (Orel/Volk muster) and Animals Crew Cap passive unchanged.

## Touched
- `_05SnowWhite.php` — Docks then Forum (`_03054` shape); WHY comment
- create-scheme SKILL mega-paragraph / references / walkthrough — stale Forum+Bazaar → Docks+Forum

## Tests
`php -l` clean. `Theah_Characters_Test` 12/12 PASS (Wildcall Animal crew-cap still green).
