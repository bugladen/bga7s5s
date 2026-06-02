# Épée Sanglante (01071) Audit

## Card Text
- **Scheme Resolve:** Add a Renown to any location.
- **Passive:** While your Musketeers are at a location with two or more Renown, they gain +1[Influence].
- **Musketeer City Action:** Your performer issues a [Combat] challenge to target opposing character. It cannot be refused. The first participant to wound their adversary steals a Renown from them.

## Scheme Resolve — Add Renown
Correct. `EventResolveScheme` transitions to `PLANNING_PHASE_RESOLVE_SCHEMES_01071`. Player picks one city location, Renown placed via `actCityLocationsForReknownSelected`. JS enter/leave/update all wired correctly.

## Passive — Musketeer +1 Influence at 2+ Renown Locations
Correct. Three handlers cover all scenarios:
- `EventCardMoved`: Musketeer moves home→city, city→home, city→city. All threshold checks correct.
- `EventReknownAddedToLocation`: If Renown crosses from <2 to ≥2, all friendly Musketeers there get +1 Influence.
- `EventRenownRemovedFromLocation`: If Renown drops from ≥2 to <2, removes the buff.

All handlers gate on `$this->Location == Game::LOCATION_PLAYER_HOME` (scheme must be in play).

## City Action — Combat Challenge
### What's correct
- Performer must be Musketeer + canChallenge() ✓
- Target must be opposing + same location ✓
- "Cannot be refused": server throws BgaUserException in `actHighDramaChallengeActionReject`, client disables Refuse button ✓
- "First to wound steals Renown": `firstWoundOccured` flag on action, checked during `EventCharacterWounded` while `IN_DUEL && EPEE_SANGLANTE_CHALLENGE_TYPE`. Reset on `EventDuelEnd`. Persists through PHP serialization via `$scheme->IsUpdated = true`. ✓
- Back transition: `backEpeeSanglante` → `HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER` ✓

### Bug found & fixed
`CHALLENGE_STAT` was never explicitly set. Every other challenge action in the codebase sets it (Action_01036, Action_01073, Action_01083, Action_01198, etc.), but Action_01071 relied on the default from `stHighDramaPlayerTurn`. While the default is `STAT_COMBAT` (matching the card text), this was fragile and inconsistent. Added explicit `globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT)`.

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01071.php` — Added explicit CHALLENGE_STAT = STAT_COMBAT
