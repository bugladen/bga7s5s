# 12 — Glossary and helpers

← [[11 — Examples|Risk Guide Example Risks To Copy]] · [[Index|Implementing a Risk Card]]

Quick reference for jargon and Theah / Game helpers you will touch while implementing Risks.

## Jargon

| Term | Meaning here |
|---|---|
| **Risk** | Faction-deck combat card (`extends Risk`) |
| **Combat card** | The Risk paid into the dueling line for a round |
| **Maneuver** | Ability on a Risk that fires when it is the combat card |
| **Performer** | Character chosen to perform an Action |
| **Participant / actor** | Your character in the current duel round |
| **Adversary / opponent** | The other duel participant |
| **Opposing** | Different controller **and** same location |
| **Engage** | Set `Engaged = true` (committed) |
| **En garde** (verb) | Set `Engaged = false` (ready) |
| **City Action** | Action with city performer pool (`RiskCityAction`) |
| **City Reaction** | Still `RiskReaction` + city-presence gate |
| **Gambling** | Combat card was locked via gamble (`DUEL_GAMBLED`) |
| **Target** | Rules word that triggers Cesca targeting interfaces |
| **Forced** | Automatic effect; no Use/Pass menu |
| **stackEvent vs queueEvent** | Stack pre-empts pending events; queue waits by priority |

## Useful Theah helpers

| Helper | Use for |
|---|---|
| `getCharactersInCityByPlayerId` | City Action / City Reaction presence |
| `getCharactersAtHomeByPlayerId` | Home pools (en garde at Home, etc.) |
| `getCharactersInPlayByPlayerId` | Home + city |
| `getOpposingCharactersAtLocation` | Opposing characters at a location |
| `getAdjacentCityLocations($loc, $includeHome)` | Move destinations |
| `getLeaderByPlayerId` | Leader Actions |
| `getDuelRoundActor` / `getDuelRoundOpponent` | Maneuver actor / adversary |
| `getDuelChallengerId` / `getDuelDefenderId` | **Character** ids — resolve `.ControllerId` for players |
| `getCurrentDuelThreat` / `getStartingDuelThreat` | Threat move / excess discard |
| `swapParticipantsInDuel` | Mid-duel participant swap (Harpoon can block) |
| `canLocationBeClaimedBy` | Claim legality |
| `cardInCity` | Is this card at a city location? |

## Useful Game globals

| Global | Use for |
|---|---|
| `CHOSEN_PERFORMER` / `CHOSEN_TARGET` | Action / challenge pipeline |
| `CHALLENGE_TYPE` / `CHALLENGE_STAT` | Challenge briefing + correlator |
| `DUEL_GAMBLED` | Gambling Maneuver / gambled discount |
| `IN_DUEL` | Forced duel-line gates |
| `EXTRA_ACTIONS` + `EXTRA_ACTION_PERFORMER` | Locked follow-up action |
| `PRESSURE_TYPE` / `PRESSURE_STAT` / `PRESSURING_PLAYER` | Pressure Actions / +1 Reactions |

## Claim control

```php
$controller = $game->getControllerForLocation($location);
// 0 = uncontrolled; otherwise a player id
```

## Engage vs En garde events

| Printed verb | Event factory | Sets |
|---|---|---|
| Engage | `createCardEngagedEvent` | `Engaged = true` |
| En garde | `createCardEngagedEvent` | `Engaged = false` |

## Event factories you will see often

- `createTransitionEvent` — enter a sub-state via `*_EVENTS` maps
- `createActionResolvedEvent` — Action finished (or comment for challenge flow)
- `createReactionTransitionEvent` / pay transition helpers — Reaction UI + pay
- `createLocationClaimedEvent` — set location controller
- `createCharacterBeingWoundedEvent` — wound (always `eventCheck` first)
- `createCardMovingEvent` — move a card/character
- `createCardDrawnEvent` — draw
- `createCardSentToLockerEvent` — Unique spend / locker
- Sorcerer start / played — required when `ISorcererAbility`

## queueEvent vs stackEvent

- **`queueEvent`** — runs by the event's own priority among pending work
- **`stackEvent`** — runs before currently pending events (pre-empt)

Use `stackEvent` when a choice must complete **before** a still-pending calc or high-priority batch. Mixing them incorrectly is a common "my cancel did nothing" / "calc ran before my choice" bug.

## Related guides

- [[Creating a Character|Implementing a Character Card]] — Techniques / challenge plumbing overlap
- [[Creating a Scheme|Implementing a Scheme Card]] — faction-deck, different lifecycle
- [[Creating a FactionAttachment|Implementing a FactionAttachment Card]] — faction gear

Back to the index → [[Implementing a Risk Card]]
