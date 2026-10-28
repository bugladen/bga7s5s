# EventHub regression tests

User asked for EventHub.php coverage after Theah suites. Prior journal said leaving EventHub alone felt right (half-stubbed queue/run) — but handleEvent itself is on Theah via the trait and is callable directly. TestTheah only stubs queueEvent/runEvents, not handleEvent.

## Approach

Call `$theah->handleEvent($event)` directly (set `$event->theah`). Assert world mutations + FakeNotify + `$queuedEvents` for cascade events (Equipping→Equipped, Moving→Moved, BeingWounded→Wounded, PressureResult→Claim).

Do **not** try to drain real runEvents or cover duel Calculate* SQL / ThreatModified DB — those need duel_round tables.

## Harness needs

- FakeGame::setReknownForLocation — getRenownForLocation already reads CityLocation::Renown; dual-write that
- FakeGame::instantiateCard + `$cardRecreateById` — CharacterDestroyed/CardRemovedFromPlay strip leading `_` via substr($pos+2), which breaks GenericCharacter ("enericCharacter"). Factory-by-id for harness recreates
- FakeGame::clearChallengeParticipantMarkers — CharacterDestroyed challenge-participant branch
- FakeGame::STAT_CHARACTERS_SENT_TO_LOCKER + record playerStatIncs
- TestTheah getDBObject: getPlayerReknown/setPlayerReknown → game (PlayerGains/LosesReknown)

## Suites shipped (38 asserts)

- `EventHub_Board_Test` — canceled, engage/engarde, Moving→Moved queue, CardMoved+attachments, claim/uncontrol, location renown (clamp + removeAll), card/player renown, combat absolute / finesse **delta** / influence absolute+dashed, CharacterTargeted CHOSEN sync, pressure result claim gate + CanBeClaimed=false, CardHidden, permanentlyHide from discard
- `EventHub_Attachments_Test` — Equipping→Equipped queue (factory returns Equipping!), Equipped attach+fromHand, steal-release `statCorrection`, unequip/move lethal→destroy queue
- `EventHub_Lifecycle_Test` — destroy→locker recreate + Brute→discard, pre-duel marker clear / IN_DUEL skip, CardSentToLocker + scheme transition drop, city-deck recreate wipe, approach reset, ChallengeIssued snapshots, BeingWounded→Wounded, ApproachCharacterPlayed

## Gotchas / WHY

- **Factory name lie:** `EventFactory::createAttachmentEquippedEvent` builds `EventAttachmentEquipping`. Hub queues the real `EventAttachmentEquipped`. Tests that need the attach mutation must `new EventAttachmentEquipped()`.
- **setReknownForLocation must NOT write CityLocation** — hub does get+set then `cityLocations->Renown += amount`. Writing CityLocation in set double-counts.
- **Generic* recreate:** EventHub strips leading `_` via `substr($pos+2)` → "enericCharacter". Use `FakeGame::$cardRecreateById`.
- Player renown goes through `Theah::getDBObject()`, not `Game::getPlayerReknown` — extended TestTheah stub.

## Not covered (honest)

- Duel Calculate* / ThreatModified / Gamble / ResolveTechnique|Maneuver (SQL + technique registry)
- CardDrawn (needs playerDrawCard deck)
- EventCharacterWounded hub body (only duel_round serialize SQL)
- Indomitable Will endEffect on CardSentToLocker
- Scheme reveal / muster / recruit notify paths (thin; card suites cover consumers)

## Verification

`php tests/run.php` → green (prior Theah run ~5674; +38 EventHub).

## Opinion

Highest-value pins are Finesse delta (easy to "simplify" back to absolute and break multi-clear on DuelEnd), steal-release strip, removeAll renown, and CanBeClaimed pressure gate. The factory Equipping/Equipped naming trap is worth the comment — almost wrote green tests against the wrong class.
