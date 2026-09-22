# Daniella Reaction_03013a Home opposing false positive

## Bug
Equipping Dark Gift on Daniella at her Home prompted Reaction_03013a with Sanjay/Raton as Sorcerer grant targets. Those characters were at *their* Homes, not opposing Daniella.

## WHY
`Game::LOCATION_PLAYER_HOME` is a single shared string. `Theah::getOpposingCharactersAtLocation("Home", daniellaPlayerId)` returns every opponent whose `Location == Home` — including enemies sitting at their own private Homes. Same footgun as Benci `_04001`, Action_04042, journal 2026-07-27-02.

Action_03013 HD availability already required `cardInCity`, so the Continuous Action menu stayed honest. The Continuous Reaction did not — it only filtered via getOpposingCharactersAtLocation.

## Fix
- `Reaction_03013a::getEligibleTargets` — return [] when at Home / not in city (Action_04042 shape)
- `performReaction` — re-check `cardInCity` before grant (Location equality alone is wrong at Home)
- `Action_03013::getEligibleTargets` + `isEligibleTarget` — same Home short-circuit for defense in depth

Do not "fix" getOpposingCharactersAtLocation globally without a careful audit — many callers assume city locations; Home callers that want "my Home only" use getCharactersAtHomeByPlayerId.
