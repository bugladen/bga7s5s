# Basic Claim pressured Discard after Blood in the Water

## Bug
Stefano basic-claimed The Grand Bazaar. Blood in the Water wounded him on engage and destroyed him into Discard-N. Log then said he pressured Discard-N and "Discard cannot be claimed."

## WHY the location flipped
`actHighDramaClaimActionPerformerChosen` queues engage, then `EventPressureOccuring` with the city site (that notify still said Grand Bazaar). Events run to completion in `HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_EVENTS` — BITW wound applies and destroy moves him — then `stHighDramaPressureLocation` re-read `$performer->Location`. That is now the discard pile, so the claim target and the influence tally both used Discard.

Same hazard as No Steel (`2026-07-17-21`): wound is MEDIUM and finishes before the pressure state. Do not delay the wound. Snapshot the site instead.

## Fix
- Basic claim writes `CHOSEN_LOCATION` at performer pick, before engage.
- `Theah::resolvePressureLocation`: only when `IS_BASIC_CLAIM_ACTION` and the performer is in discard/locker, and the snapshot is a city site, use the snapshot. Still-in-city performers keep current Location (a later city move still follows them). Other pressure is unchanged — those flows already clear `CHOSEN_PERFORMER` or accept current location (03054).
- Did not widen the fallback to every pressure. `CHOSEN_LOCATION` lives until `stNextPlayer`, so a stale docks snapshot could hijack a later Forum pressure whose performer died.

## Influence after the fix
Totals are characters still at the city site. Dead Stefano is not there, so his 1 Influence does not count. The log's Baconat0r:(1) was him sitting in Discard. A lone destroyed claimer now fails pressure at the real location instead of "successfully" pressuring the discard pile.
