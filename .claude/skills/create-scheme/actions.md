> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Actions on Schemes

When a scheme has a City Action / Action / Leader City Action / Risk City Action, the action lives in a separate file `actions/Action_NNNNN.php` extending the appropriate base class:

| Card phrase | Action base class |
|---|---|
| **`<b>City Action:</b>`** on a scheme | `SchemeCityAction` |
| **`<b>Leader City Action:</b>`** on a scheme | `SchemeCityAction` (with a `hasTrait("Leader")` performer-filter) |
| **`<b>Risk City Action:</b>`** | `RiskCityAction` (the Risk shape — pressures/wagers; see `create-character`) |

`SchemeCityAction extends Scheme` — so the action class IS the scheme. (Same shape as `CharacterAction extends Character`.) The action class is what gets registered in `$this->Actions = [new Action_NNNNN()]` on the scheme card.

Pre-commit hook: `SchemeCityAction` subclasses must call `createActionResolvedEvent()`. Don't call `setUsed` / `resetPlayerPassCount` / `announceAction` directly — those run centrally during `actHighDramaInPlayActionConfirm` (same as character actions).

Reference: `_01044`'s `Action_01044`, `_02014`'s `Action_02014`, `_03029`'s `Action_03029`, `_03053`'s `Action_03053`, `_03054`'s `Action_03054`, `_03061`–`_03063`.

**Action-object persistence:** public fields on the Action (`$MoveMode`, `$pendingMusterId`, …) survive only if you call `$game->updateCardObjectInDb($owner)` after mutating them. `$owner->IsUpdated = true` alone is **not** flushed before `stRunEvents` rebuilds cards from DB (learned on `_03029` / `_03062` / `_03063`).

### Pattern H — Immediate-resolve City Action (no HD sub-state)

Use when the printed City Action needs a performer but **no further player picks** after confirm — cost + effects all resolve on `EventActionTriggered`. Curry Favor (`_03053`): Spend a Renown • Claim your performer's location. Each opponent draws a card.

**Do NOT invent an HD GameState** for this shape. The framework already runs performer selection before `EventActionTriggered` when `RequiresPerformerSelected = true`.

**Flow:**

1. `SchemeCityAction` + `RequiresPerformerSelected = true`.
2. `isAvailableToPlayer`: parent + cost gate (`getPlayerReknown >= 1` for "Spend a Renown") + `count(getPerformersForAction) > 0`.
3. `getPerformersForAction`: start from parent (city characters), then filter full legality. When Claim is the payoff, keep only `$theah->canLocationBeClaimedBy($playerId, $performer->Location)` — offering an unclaimable performer wastes the Renown spend (same discipline as `Action_01103a` / `Action_03cd13`).
4. `EventActionTriggered`: re-validate cost + performer + `cardInCity`; queue cost event; queue claim (or notify cannot claim); queue trailing effects (e.g. opponent draws); queue `createActionResolvedEvent`.
5. Trailing sentences after Claim (e.g. "Each opponent draws") still fire even if claim is blocked at resolve — they are separate effects. Availability already tried to prevent dead claims.

**"Spend a Renown" vs location Renown:**

| Printed text | Event |
|---|---|
| **Spend a Renown** (no location) | `createPlayerLosesReknownEvent($playerId, 1)` — player score |
| **Remove a Renown from [Location]** / this location | `createRenownRemovedFromLocationEvent(...)` — location token |

**Opponent draws (no pick):**

```php
foreach ($game->loadPlayersBasicInfos() as $opponentId => $_)
{
    $opponentId = (int)$opponentId;
    if ($opponentId == $playerId) continue;
    $event->theah->queueEvent(EventFactory::createCardDrawnEvent($opponentId, $owner->getInjectCode()));
}
```

Reference: `Action_03053`. Character parallels for direct claim without pressure: `Action_01103a`, `Action_02029`.

### Pattern I — Wound-then-pressure City Action (Resolve / Combat / Finesse)

Use when the printed City Action pays a **wound on the performer** (often **unequipped**) and then **pressures** their location; success usually opens a character pick. Canonical: `_03054` (No Steel, No Surrender). Risk parallel for Resolve pressure + success pick: `Action_01105` (engage only — no wound cost, no Home move).

**Flow:**

1. `SchemeCityAction` + `IAbilityThatTargetsCharacters` (if success targets a character) + `RequiresPerformerSelected = true`.
2. `getPerformersForAction` / `isAvailableToPlayer`: filter full legality — unequipped (`count(Attachments) == 0`), `canPressure($stat)`, ≥1 opposing character at location when the success payoff needs a target. Availability = `count(getPerformersForAction) > 0`.
3. `EventActionTriggered`:
   - Capture `$location = $performer->Location`.
   - Set `PRESSURING_PLAYER`, `PRESSURE_TYPE = NORMAL`, `PRESSURE_STAT` (e.g. `STAT_RESOLVE`).
   - Set `CHOSEN_LOCATION = $location`, `CHOSEN_CARD = $performerId`, then **`CHOSEN_PERFORMER = 0`**.
   - Queue `createCharacterBeingWoundedEvent` for the performer.
   - Queue `createPressureOccuringEvent(..., $performerId, $location, $pressureStats)` (still pass the real performer id for messaging) + `createTransitionEvent(..., "pressureLocation", $this->Id)`.
4. `EventLocationPressureResult` when `$event->abilityId == $this->Id`:
   - **Success + opposing at `$event->location`:** `createTransitionEvent(..., "NNNNN", $this->Id)` into HD pick state — **return** (do not resolve yet).
   - **Success + no opposing / failure:** notify if needed, then **always** `createActionResolvedEvent`. WHY: hub only auto-resolves `highDramaBasicAction` pressures; ability pressures must resolve themselves. Do not copy `Action_01105`'s silent failure path.
5. HD pick state: `isValidTargetForAbility` vs `CHOSEN_LOCATION`; on confirm queue target wound, then Home move if non-lethal, then `createActionResolvedEvent`, `nextState("")`.

**WHY clear `CHOSEN_PERFORMER` before pressure:**

`stHighDramaPressureLocation` does:

```php
$performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
if ($performerId != 0) {
    $performer = $this->getCardObjectFromDb($performerId);
    $location = $performer->Location;  // locker if destroyed!
} else {
    $location = $this->globals->get(Game::CHOSEN_LOCATION);
}
```

`EventCharacterBeingWounded` queues `EventCharacterWounded` at medium priority; `EventTransition` is priority 8. The wound (and possible destroy→locker) can apply **before** the pressure state runs. Leaving `CHOSEN_PERFORMER` set would pressure the locker. Clearing it forces the captured city `CHOSEN_LOCATION`.

**Lethal wound + "move them Home":**

```php
$willDie = ($target->Wounds + 1 >= $target->ModifiedResolve);
queue createCharacterBeingWoundedEvent(...)
if (! $willDie) {
    queue createCardMovingEvent(..., LOCATION_PLAYER_HOME, engage: false, ...)
}
```

WHY skip Home when lethal: a later `EventCardMoved` after destroy can yank the character from the locker back to Home. Destroy-at-city is the correct lethal outcome for "wound and move Home" when they cannot survive the wound.

**Resolve pressure note:** `getResolvePressureValue` returns `ModifiedResolve` (wounds ignored for the total — same idea as Drinking Games' "Ignore wounds"). Characters must still **be at the location** to count; a destroyed performer does not.

Reference: `Action_03054`, `State_highDramaPhase03054`. Compare `Action_01105` / `Action_03040` / `Action_03cd20` for pressure + `EventLocationPressureResult` shapes.

### Pattern J — Move Renown or available attachment to another City location

Use when the printed City Action is **"Move a Renown or an available attachment from your performer's location to a different City location"** (often trait-prefixed: Scoundrel). Canonical: `_03063` (Smuggling Run).

**Flow:**

1. `SchemeCityAction` (+ `IAbilityThatTargetsCards` when attachments are selectable). `RequiresPerformerSelected = true`.
2. `getPerformersForAction`: trait gate **and** location has something to move — `CityLocation->Renown > 0` **or** `count(getAvailableAttachmentsAtLocation) > 0`. `isAvailableToPlayer` = `count(getPerformersForAction) > 0`.
3. `EventActionTriggered`: validate performer + movable thing → clear `$MoveMode` / `CHOSEN_CARD` → `updateCardObjectInDb` → transition `"NNNNN"`.
4. HD state `NNNNN` (choose what):
   - **Renown:** action button → `actFromCardWithId` with **id `0`**. WHY 0: card ids are never 0, so no collision with attachment ids.
   - **Attachment:** `highlightCardsAsSelectable(attachmentsInPlay)` + Confirm → `actFromCardWithId` with attachment id. Re-validate `Location == performer->Location && !isAttached()`.
   - Set `$MoveMode` (RENOWN vs ATTACHMENT); for attachment also `CHOSEN_CARD = id`; `updateCardObjectInDb`; `nextState("thingChosen")`.
5. HD state `NNNNN_2` (destination): city locations excluding performer's current location (`actFromCardWithLocations` / `actFromActionWithIds`). Named success transition + `"back"` (never `""` alongside `"back"`).
6. Resolve:
   - Renown: batch `createRenownMovingBetweenLocationsEvent` + `createRenownRemovedFromLocationEvent` + `createRenownAddedToLocationEvent(..., $isMove = true)` under one `batchId` (same idiom as `Reaction_03041`).
   - Attachment: `createCardMovingEvent($playerId, $attachmentId, $from, $to, engage: false, ...)`. WHY `engage=false`: unattached city cards; no engage printed.
   - Clear mode/globals, `updateCardObjectInDb`, `createActionResolvedEvent`, `nextState("locationChosen")`.

**JS:** State 1 shows Move Renown button only when `renownAvailable`; Confirm Attachment only when `attachmentsInPlay.length > 0`. State 2: city location select + Back + Confirm.

Reference: `Action_03063`, `State_highDramaPhase03063{,_2}`. Attachment-pick parallel: `Action_02047`. Renown-move batch: `Reaction_03041`.

### Pattern K — Muster from The Locker; return at end of Dusk

Use when the City Action musters a character from The Locker (often with a wound cost and temporary trait grants) and says they return to The Locker at end of Dusk. Canonical: `_03062` (Deal with the Devil).

**Flow:**

1. Trait-gated `SchemeCityAction` (e.g. Villain). HD state: locker `chooseList` filtered by printed exclusions (e.g. non-Undead, non-Mercenary).
2. On confirm: wound performer (if printed) → `createCharacterMusteredEvent` at performer location → `createActionResolvedEvent`.
3. **Grant traits after muster in play:** stash `$pendingMusterId` on the action + `updateCardObjectInDb`; on `EventCharacterMustered` matching that id, `addTrait` Monster/Undead (or whatever is printed), clear pending, flush again. WHY after muster: trait notifies should fire once the character is in play, not while still in the locker.
4. **Dusk return on the Character, not the Action/Scheme.** Chosen schemes are sent to the locker in `stDuskPhaseCleanup` **before** `EventDuskPhaseEnd`. `buildCity()` does not load locker cards into `$theah->cards`, so Action/Scheme `handleEvent` never sees DuskPhaseEnd. Stamp a condition on the mustered character (e.g. `Game::DEAL_WITH_THE_DEVIL`); Character.php on `EventDuskPhaseEnd` strips granted traits, unequips, queues `createCardSentToLockerEvent`.
5. **Strip granted traits before re-locker.** `EventCardSentToLocker` does not recreate the card (unlike destroy). Leaving Undead (etc.) on them permanently fails the next muster’s non-Undead filter.

**JS:** locker chooseList — coerce ids with `Number()` on both args and locker card ids (client types drift).

Reference: `Action_03062`, `State_highDramaPhase03062`, Character dusk condition path.

### Move performer to a location with a wounded enemy

Use when the City Action is **"Move your performer to a location with a wounded enemy"** (often **Duelist City Action**). Canonical: `_04004` (Blood Money).

**Flow:**

1. `SchemeCityAction` + `RequiresPerformerSelected = true`. Trait gate in `getPerformersForAction`.
2. Destinations helper: other City locations (`Name != performer->Location`) that have ≥1 character with `ControllerId != performer->ControllerId` and `Wounds > 0`.
3. Availability = `count(getPerformersForAction) > 0` where performers also have `count(destinations) > 0` — do not offer a dead move.
4. `EventActionTriggered` → re-validate → `createTransitionEvent(..., "NNNNN", $this->Id)` into one HD location-pick state.
5. Args expose `performerId` + `locationIds`. On confirm: `createCardMovingEvent(..., engage=false)` + `createActionResolvedEvent`.
6. Named success (`"locationChosen"`) if the state also has `"zombie"` (or `"back"`) — do not use `""` alongside siblings.

**JS:** city location select from `locationIds` + highlight performer + Confirm. Leave: `resetCityLocations` + unhighlight.

Reference: `Action_04004`, `State_highDramaPhase04004`. Adjacent-only move parallel: `Action_01059`.

### High Drama action sub-states (City Action / Sorcerer City Action)

Planning resolve sub-states use `PLANNING_PHASE_RESOLVE_SCHEMES_*` (`26<NNNNN>`) and `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`. **Scheme actions played during High Drama use a different map:**

| Piece | Where |
|---|---|
| State constant | `States::HIGH_DRAMA_PLAYER_TURN_<NNNNN>` = `40<NNNNN>` (append `2`, `3` for follow-on steps) |
| Transition map | `states.inc.php` → `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` — keys like `"03029"`, `"03029_2"`, `"03029_3"` |
| State class | `modules/php/States/<expansion>/State_highDramaPhase<NNNNN>.php` (name: `highDramaPhase<NNNNN>`) |
| Action logic | `actions/Action_NNNNN.php` — `handleEvent` on `EventActionTriggered` queues `createTransitionEvent($playerId, $owner->Id, "NNNNN", $this->Id)` |
| JS | Same triple as resolve states, but in `OnEnteringState.<expansion>.js` etc. under `highDramaPhase<NNNNN>` keys |

**Performer-required actions:** set `$this->RequiresPerformerSelected = true` on the action. The framework sets `Game::CHOSEN_PERFORMER` before your first sub-state runs.

**Multi-step with Back:** middle/final states expose `#[PossibleAction] actBack()` and a `"back"` transition to the prior state; JS adds `<` calling `actBack`. Reference: `State_highDramaPhase03029_2`, `State_highDramaPhase03cd01_2`. **If the state also has a success transition, that success key must be named — never `""` alongside `"back"`** (see GameState transition pitfall).

**Sorcerer performer gate on a scheme City Action:** override `getPerformersForAction` to filter `hasTrait("Sorcerer")`. In `isAvailableToPlayer`, loop performers and return true only if at least one has a legal target for at least one printed branch — don't gate availability on a single fixed performer.

**`SchemeCityAction` availability:** the base `SchemeAction` requires the scheme owner card at `LOCATION_PLAYER_HOME`. That is correct for normal schemes (chosen schemes stay at Home all day — see lifecycle). Only override `isAvailableToPlayer` like `_02045` when the scheme is **placed on a city location** and the action keys off that placement.

**Sorcerer wound + move event order** (matches Porté on characters):

```php
createSorcererAbilityStartEvent(...)
createCharacterBeingWoundedEvent($performer->Id, ...)  // "Wound your performer"
createCardMovingEvent(...)
createSorcererAbilityPlayedEvent(...)
createActionResolvedEvent(...)
```

**Character targeting validation:** even when picks go through sub-states (not the challenge target UI), implement `isValidTargetForAbility(Game $game, Character $character): array` and call it from `actFromActionWithId` — JS can be tampered with.

### Pattern E — Engage performer, different character issues challenge

Use when the printed text separates **who engages** (performer) from **who issues the challenge** (another character at the same location). Reference: `_03030` (Diplomat engages, Duelist challenges), `Action_03003` on Don Constanzo (Thug challenges — no framework performer pick).

**Flow (`_03030` shape):**

1. `RequiresPerformerSelected = true`. `getPerformersForAction` filters performer trait **and** full action legality (see below).
2. `EventActionTriggered`: validate performer → `createCardEngagedEvent` for performer if not engaged → `Game::CHOSEN_CARD = $performerId` (preserve while challenger takes over `CHOSEN_PERFORMER`) → `createTransitionEvent(..., "NNNNN")`.
3. HD state `NNNNN`: pick challenger (e.g. Duelist at performer's location) → `CHOSEN_PERFORMER = $challengerId` → `nextState("…Chosen")`.
4. HD state `NNNNN_2`: pick opposing target → set `CHALLENGE_STAT` / custom `CHALLENGE_TYPE` → `createTransitionEvent(..., "NNNNN_2")` where `"NNNNN_2"` maps to `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` in `states.inc.php` (same as `03003_2`).

**`getPerformersForAction` must encode full legality for the performer picker** — not just the trait gate. For `_03030`, each Diplomat must pass ALL of:

- `hasTrait("Diplomat")` and `!Engaged` (card says "Engage your performer")
- at least one eligible challenger at `$performer->Location` (e.g. Duelist with `canChallenge($theah)`)
- at least one opposing character at `$performer->Location`

`isAvailableToPlayer` should be `return count($this->getPerformersForAction($playerId, $theah)) > 0` so availability matches the picker.

**Engagement:** only engage the printed performer. Do **not** rely on `stIssueChallenge`'s auto-engage for the challenger — register a custom `CHALLENGE_TYPE` excluded from that list (same idea as `DON_CONSTANZO_CHALLENGE_TYPE`).

**Challenger eligibility:** if the card doesn't say "unengaged \<trait\>", allow already-engaged challengers when `canChallenge($theah)` permits (see `Action_03003` Thug comment).

### Custom `CHALLENGE_TYPE` for intervention gates

When the text restricts who may intervene (or refuse — see `create-risk` skill), add `final const …_CHALLENGE_TYPE = N` in `Game.php` and enforce in all three places that filter intervene UI/server checks:

| Location | Role |
|---|---|
| `Theah::interventionCheck` | Server-side reject on illegal intervene click |
| `ArgumentsTrait` (intervene args) | Filter `ids` so UI only shows legal interveners |
| `Reaction_02058::getValidPerformers` | Adjacent external-intervene reaction respects the same gate |

Reference: `LEGENDARY_REPUTATION_CHALLENGE_TYPE` (Leaders only), `AJA_CHALLENGE_TYPE` (3+ Finesse), `SWORN_SWORDS_CHALLENGE_TYPE` (Duelists only on `_03030`).

**Accept-time threat bonus:** handle in the action's `handleEvent` on `EventGenerateChallengeThreat` when `$challengeType` matches — increment `$event->actorThreat` for "your participant" only.

### Engage-and-challenge scheme City Action (same performer issues)

Use when the text is **"Engage your performer • They issue a [Stat] challenge to target opposing character"** (performer = challenger). This is simpler than Pattern E. Reference: `_03042` (scheme), `Action_03021` Cornered (Risk parallel).

**Flow:**

1. `RequiresPerformerSelected = true`. `getPerformersForAction` filters `canChallenge && !Engaged` with ≥1 opposing target at location. `isAvailableToPlayer` = `count(getPerformersForAction) > 0`.
2. `EventActionTriggered`: engage performer if not engaged → set `CHALLENGE_STAT` + custom `CHALLENGE_TYPE` → `createTransitionEvent(..., "NNNNN", $this->Id)`.
3. `"NNNNN"` under `HIGH_DRAMA_PLAYER_TURN_EVENTS` maps to **`HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`** (framework target picker — no custom HD state for the target pick). Implement `isValidTargetForAbility` for server validation.
4. Mint a `CHALLENGE_TYPE` kept **out of** `stIssueChallenge`'s auto-engage list. WHY: engage already ran in step 2; `NORMAL_CHALLENGE_TYPE` would double-engage. Same idea as Cornered / Sanjay / Don Constanzo.

**Engagement trichotomy** (see `create-character` Pattern F): (a) Engage printed → engage in ActionTriggered + custom type out of auto-engage (`_03042`, Cornered); (b) conditional engage; (c) never engages (Sanjay). Do not copy the wrong case.

### Pattern G — Discard-to-refuse (conditional refuse cost)

Use when refuse is allowed only by paying a hand card under a trait condition (e.g. "If your performer is a Duelist, it can only be refused by discarding a card").

**Rules reading:**
- **Intervene ≠ refuse.** Do not gate intervene. (Triskelion precedent: intervening accepts.)
- Empty hand + condition met → **cannot refuse** (JS disable + server `UserException`). "By discarding" implies a card must exist.
- Condition not met → free refuse under the same `CHALLENGE_TYPE`.

**Always mint the correlator `CHALLENGE_TYPE` for every use of the action**, not only when the performer currently has the trait. WHY: engage-out-of-auto-list is needed for all performers; the Duelist (or other) check runs only at refuse time.

**Wiring (lockstep):**

| Piece | What |
|---|---|
| `Game.php` | `WHEN_LEAST_EXPECTED_CHALLENGE_TYPE = N` (next free int) |
| `seventhseacityoffivesails.js` | Matching `this.WHEN_LEAST_EXPECTED_CHALLENGE_TYPE = N` (needed if JS gates Refuse) |
| `FrameworkActionsTrait::actHighDramaChallengeActionReject` | If type + trait + empty hand → throw; if type + trait + hand → `nextState("NNNNN")` (do **not** queue `ChallengeRejected` yet); else normal reject |
| `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge` | Expose `mustDiscardToRefuse` (type + performer trait) and `defenderHandCount` |
| `states.inc.php` `ACCEPT_CHALLENGE.transitions` | **`"NNNNN" => States::HIGH_DRAMA_PLAYER_TURN_NNNNN`** — card-number key, not a reusable name like `"discardToRefuse"`. Eddie: card-specific keys match other transition naming patterns. |
| State class | `State_highDramaPhaseNNNNN` — hand discard + Back. Success transition **must be named** (see GameState transition pitfall below). |
| Action `actFromActionWithId` | Validate hand card → queue `createCardDiscardedFromHandEvent(..., $asEffect = true)` → queue `createChallengeRejectedEvent` → `CHALLENGE_ACCEPTED = false` → `nextState("cardDiscarded")` |
| JS | Accept-challenge: relabel Refuse / disable when `mustDiscardToRefuse && defenderHandCount < 1`. Discard state: faf triple + `EventHandlers` enable on selection |

**Why `actFromCardWithId` reaches the Action during refuse:** `TRANSITION_SOURCE_ID` / `TRANSITION_INTERNAL_ID` set by the original `createTransitionEvent` persist through the challenge flow. `Card::actFromCardWithId` routes via the action id to `actFromActionWithId`.

**After discard, resume the normal reject path:** `cardDiscarded` → `HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT` (same as ACCEPT_CHALLENGE's `""` after a free refuse). Do not invent a separate reject-events entry.

### GameState transition pitfall (BGA)

When a GameState class has **more than one** transition (e.g. success + `"back"`):

- **Do not** use `""` as a transition key alongside others. Calling `nextState("")` (or bare `nextState()`) yields **"More than one possible transition at this state"**.
- Use an explicit success name: `"cardDiscarded"`, `"done"`, `"targetChosen"`, etc. Call `nextState("cardDiscarded")`.
- Canonical: `State_highDramaPhase03038a` (`cardDiscarded`), `State_highDramaPhase03029_2` (`done` + `back`), `State_highDramaPhase03042` (`cardDiscarded` + `back`).

Planning-End Forced states that only have `"" => PLANNING_PHASE_END_EVENTS` (single transition) can keep `nextState("")` — the ambiguity only appears when multiple keys exist.
