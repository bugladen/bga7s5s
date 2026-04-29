# Action_01094 (Engarde Aníbal) — getCityLocation crash on Player Home

## The bug

User pasted a prod stack trace:

```
Uncaught Exception: City location Player Home not found
  Theah.php:554
  Action_01094.php:32  $theah->getCityLocation($owner->Location)
  Theah.php:1242       playerHasInPlayActions(...)
  ArgumentsTrait.php:81
  ...
```

The crash happened during `playerHasInPlayActions`, i.e. while the engine was
asking "what actions can this player take?" — Action_01094 was being polled
for availability and threw because Aníbal's `Location == "Player Home"`.

## Why it happened

`Action_01094::isAvailableToPlayer` did:

```php
if (!$owner->Engaged) return false;
$location = $theah->getCityLocation($owner->Location);  // throws on Player Home
return $location->Renown == 0;
```

The `Engaged` flag is the "exhausted in pressure" state (NOT the same as
"engardé" — confusingly, `EventCardEngarded` sets `Engaged = false`). A
character can end up Engaged at Player Home: e.g., engaged during a pressure
in city, then moved home by another card while the engaged flag persisted.
Plenty of cards move characters home; they don't all clear `Engaged`. So the
gate let through, and `getCityLocation('Player Home')` blew up because Player
Home isn't a city location at all.

The card text — *"If Aníbal's location has no Renown • En garde him."* — only
makes sense at a city location anyway (Renown is a city-location property),
so the action shouldn't be available at Player Home regardless of the
Engaged flag.

## Fix

Added an explicit `$theah->cardInCity($owner)` short-circuit before the
`getCityLocation` call. Tiny, local, matches the card text. Left a WHY
comment in the source explaining the Engaged-at-Player-Home edge case so a
future reader doesn't "simplify" the guard back out.

## Why this fix vs. alternatives

- **Make `getCityLocation` return null instead of throwing.** Tempting but
  much bigger blast radius — every existing caller assumes it throws or
  returns a value, and many would silently break on non-city locations.
- **Clear `Engaged` whenever a character moves to Player Home.** Probably
  *also* correct game-rules-wise, but a much riskier change to make from a
  single stack trace — there are many move paths and many callers reading
  `Engaged`. Could regress unrelated cards.
- **Guard the specific call site** (what I did). Smallest surface area;
  matches what the card text already implies; same shape used by Action_01130
  via `getCharactersInCityByPlayerId` (which also excludes Player Home).

## Other cards with the same shape

A grep of `getCityLocation\(\$.*Location\)` turns up ~20 other cards calling
`getCityLocation($x->Location)` directly. Many of them appear to gate on
performer/owner being in the city upstream (e.g. via
`getCharactersInCityByPlayerId`), but I didn't audit each one. If similar
crashes show up later, the same fix pattern should apply per-card.

Specifically, `Action_02023.php:43` looks structurally identical to 01094
(`$location = $theah->getCityLocation($owner->Location)` with no city
guard before it). Worth a look next time someone is in there. Not fixing
preemptively — no reported crash, and I don't want to fan out a one-line bug
fix into a multi-card audit without the user asking.

## Not fixed

- The `Engaged`-persisting-after-move-home thing might still be a latent bug
  for other code paths. I didn't chase it. If the user reports oddness with
  Engaged characters at home, that's where to start.
