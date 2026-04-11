# Odette Dubois D'Arrent (01062) Audit

## Card Text
"When Odette is challenged, if you have an en garde Musketeer at this location, they may intervene without engaging."
"City Action: Move your adjacent Duelist to this location."
"Reaction: When a challenge is accepted at this location • Move an adjacent Renown to this location."

## Files Reviewed
- `modules/php/cards/_7s5s/_01062.php` - Character class
- `modules/php/cards/_7s5s/actions/Action_01062.php` - City Action implementation
- `modules/php/cards/_7s5s/reactions/Reaction_01062.php` - Reaction implementation
- `modules/php/FrameworkActionsTrait.php` (lines 1272-1282) - Passive ability (Musketeer intervene without engaging)
- `modules/php/ArgumentsTrait.php` (lines 577-633) - Intervene eligibility filtering
- `modules/php/cards/_7s5s/reactions/Reaction_01100.php` - Reference pattern for EventCharacterIntervened handling

## Verdict: TWO BUGS FIXED

### Part 1: Passive - Musketeer intervene without engaging (PASS)
Implemented in `FrameworkActionsTrait::actHighDramaChallengeActionIntervene`. When `$target instanceof _01062 && $character->hasTrait("Musketeer")`, `$engageRequired = false` skips the engage event. The "en garde" requirement is naturally enforced by `argsHighDramaChallengeActionAcceptChallenge` which filters out engaged characters from intervene candidates. No issues.

### Part 2: City Action - Move adjacent Duelist (BUG FIXED)
**Fixed:** `isAvailableToPlayer` used `$odette->Location == Game::LOCATION_PLAYER_HOME` instead of `!$theah->cardInCity($odette)`. Every other character City Action (Action_01068, Action_01041) uses `cardInCity`. The old check was weaker — `cardInCity` checks against the five specific city locations, while "not home" would pass for any non-home location. Changed to `!$theah->cardInCity($odette)` for consistency and correctness.

WHY not just "not home": `cardInCity` explicitly checks against the five valid city locations (Oles Inn, Docks, Forum, Bazaar, Governor's Garden). The "not home" check is a superset that could theoretically pass for edge-case locations. The codebase convention is clear: City Actions use `cardInCity`.

### Part 3: Reaction - Move adjacent Renown (BUG FIXED)
**Fixed:** `EventCharacterIntervened` handler used `$event->newTargetId` (the intervener/new defender) but named the variable `$challenger` and used it for location checking. Changed to `$event->theah->game->globals->get(Game::CHOSEN_PERFORMER)` to get the actual challenger, matching the established pattern in `Reaction_01100`.

WHY the old code often worked anyway: Challenges require all parties (challenger, defender, intervener) to be at the same city location, so checking the intervener's location vs Odette's was usually equivalent to checking the challenger's. But it was semantically wrong and fragile.

The reaction correctly handles both `EventChallengeAccepted` (direct accept) and `EventCharacterIntervened` (someone intervenes, which also constitutes the challenge proceeding). Renown movement (remove from adjacent, add to Odette's location with `$isMove = true`) follows the standard pattern.
