# Theah.php regression tests

User asked for regression tests for Theah.php after the UtilitiesTrait suite wave.

## Approach

Same pattern as UtilitiesTrait: exercise real Theah methods through TestWorld/TestTheah (RAM-seeded cards, no DB rebuild). Do **not** try to cover `buildCity` / real `queueEvent` / `runEvents` / duel-table SQL — TestTheah stubs those for a reason, and card suites already lock those flows.

Split into three framework suites (mirror UtilitiesTrait splitting):

- `Theah_Locations_Test` — locationInCity/cardIn*, adjacency/outer/non-adjacent, claim/uncontrol dual-write + Discard/Locker guards, resolvePressureLocation (Basic Claim corpse), createEvent
- `Theah_Characters_Test` — world lookups, in-play filters, opposing chars/mercs, influence sum, crew-cap Brute + Wildcall Animal exemption, available attachments
- `Theah_Capabilities_Test` — playerCan*/characterCan* for move/recruit/equip/challenge/claim, hand brutes, zombie cache, applyAbsentActorBaseChallengeThreat (snapshot / DB fallback / skip / still-in-city)

## Harness additions on FakeGame

Needed so Theah capability paths don't fatal:

- `handHasAttachments` → `getAttachmentsInHand` (no `: bool` — FrameworkActionsGame overrides without a return type; PHP fatal if parent declares one)
- `getChallengeLastKnownCharacter` + `$challengeLastKnownById` for absent-actor threat snapshot branch
- `getCanBeClaimedForLocation` / `getCanBecomeUncontrolledForLocation` (defaults true) so dual-write asserts can read globals
- `$playerZombies` + `loadPlayersBasicInfos` sets `player_zombie` for isPlayerZombie

## Gotchas / WHY notes for next agent

- TestTheah `seedDefaultCityLocations` always seeds all **five** city sites (Ole's + Garden even at 2p). Adjacency uses PLAYER_COUNT global correctly, but `getNonAdjacentCityLocations` walks seeded cityLocations — so Forum's non-adjacent set is Ole's+Garden, not empty. Don't "fix" the seed to match production buildCity without updating that test.
- `resolvePressureLocation` Basic Claim corpse path needs both `characterIsInDiscardOrLocker` true **and** a city `capturedLocation`. Non-basic still follows the corpse's Discard Location (intentional).
- Zombie ids are cached on first `isPlayerZombie` call (`$zombiePlayerIds`); changing `$playerZombies` mid-request won't clear the cache — test asserts that on purpose.

## Not covered (honest)

- Real `buildCity` / DB load / discard-pile Location repair / Dandy influence heal / IW+Leshiye backfills
- Real EventHub `queueEvent`/`runEvents` drain
- Duel threat/round SQL helpers (TestTheah stubs; card suites cover consumers)
- `previewChallengeThreat` full walk (needs Actions/Reactions registered + non-stubbed buildCity)
- `actionAvailableToPerformer` / in-play/hand action menus (heavy card+action wiring; FrameworkActions suites cover)
- `calculateInHandPayDiscount` / discard-pile salvage helpers
- `_01178` special challenge branch in playerCanBasicChallenge

## Verification

`php tests/run.php` → green (prior UtilitiesTrait run was 5636; +38 Theah asserts).

## Opinion

High-value surface was the claim/uncontrol guards and capability gates — those are silent fatals / wrong menus when they regress. Crew-cap Animal+Wildcall was worth including because the Location==Home end-condition is easy to "simplify" wrongly. Leaving EventHub alone felt right; it's a different beast and half-stubbed already.
