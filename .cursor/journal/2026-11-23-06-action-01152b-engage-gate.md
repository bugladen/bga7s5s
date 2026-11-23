# Action_01152b Engage performer gate

## Context
User: action choosable when all at location are engaged; performer valid only if a non-engaged character is at the location. Clarified as **b** (Engage), not a (En Garde).

## Bug
`isAvailableToPlayer` only required any city character. `getPerformersForAction` returned every city character. Args already filtered `!Engaged`, so you could offer the action / pick a performer at an all-engaged location and softlock on empty `characterIds`.

## Fix
Mirror `Action_01152a`'s Engaged location gate, inverted for Engage:
- `getPerformersForAction` — only performers whose location has ≥1 `!Engaged` character
- `isAvailableToPlayer` — `count(getPerformersForAction) > 0`

WHY share the helper for availability: same predicate as picker; avoids availability/performer drift (01152a already computed the list inline twice — we reuse getPerformers here).

## Follow-up: 01152a same picker hole
User asked if a had the same bug — yes: availability already gated on Engaged-at-location, but `getPerformersForAction` returned every city character. Mirrored the b shape: filter in `getPerformersForAction`, availability = `count(...) > 0`. Still did **not** touch the Engaged-vs-Engarded event bug.

## Tests
- `Action_01152b_Test`: unavailable when all engaged; performers exclude all-engaged locations.
- `Action_01152a_Test`: performers exclude locations with no Engaged target.
