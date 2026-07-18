> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern A — City Action (`RiskCityAction`)

`RiskCityAction extends RiskAction` and adds a built-in "at least one of my characters is in the city" check.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_NNNNN extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("...");
        $this->RequiresPerformerSelected = true;   // "Your performer …"
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_filter($performers, fn(Character $c) => $c->canChallenge());

        foreach ($performers as $performer)
        {
            if (count($this->getValidTargets($theah, $performer)) > 0) return true;
        }
        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, function (Character $p) use ($theah) {
            if (! $p->canChallenge()) return false;
            return count($this->getValidTargets($theah, $p)) > 0;
        }));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
            return [false, $game->translate("Target must be controlled by an opponent.")];
        if ($character->Location != $performer->Location)
            return [false, $game->translate("Target must be at your performer's location.")];
        // ... card-specific target predicates (Influence cap, trait, etc.)
        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "NNNNN", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }

    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $adversaries = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        // ... apply card-specific filter (Influence cap, trait, etc.)
        return array_values($adversaries);
    }
}
```

State wiring: `"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` map. This is the shared "pick a target via `IAbilityThatTargetsCharacters`" state — no new state class needed for vanilla Combat challenges.

References: `Action_01083` (Leader-only intervention, custom challenge type), `Action_03008` (Influence-gated target).

### Custom challenge type when intervention/refusal differ OR carry side effects

`Game::NORMAL_CHALLENGE_TYPE` is the default and works for any "target-only" restriction (the Influence gate in `_03008`, for example). Add a new challenge-type constant in `Game.php` when **either**:

1. **Intervention or refusal *gates* differ from normal** — "Only Leaders can intervene" (`LEGENDARY_REPUTATION_CHALLENGE_TYPE` in `_01083`), "Only characters with 3 Finesse or more may intervene or refuse" (`AJA_CHALLENGE_TYPE`). The framework reads CHALLENGE_TYPE in `Theah::interventionCheck` to enforce these gates.
2. **Intervention or refusal carries a side effect attached to the issuing card** — "If they refuse, engage them" + "Wound any character that intervenes" (`CORNERED_CHALLENGE_TYPE` in `_03021`). The gates themselves stay normal (anyone can refuse or intervene), but the **Risk class needs a correlator** to tell "this challenge is mine" inside its `EventChallengeRejected` / `EventCharacterIntervened` handlers.

See the existing list in `modules/php/Game.php` for the catalog.

#### The "actionId on challenge events" trap (do NOT do this)

`EventChallengeRejected` exposes `challengerId` and `targetId` — **no `actionId`**.
`EventCharacterIntervened` exposes `playerId`, `oldTargetId`, `newTargetId` — **no `actionId`**.

A Risk class whose printed text attaches a side effect to refuse/intervene cannot gate its handler on `$event->actionId == $this->Id` — that property does not exist on either event, the comparison is always false (and may emit an undefined-property warning), and the effect silently dies.

Why a Risk-class handler can't pin "this is my challenge?" off `challengerId` alone: the challenger is the *performer*, picked at play time from a pool of characters. The Risk has no stable identity in the challenger field. Two cards could legitimately have the same performer issue separate challenges in the same turn.

The right correlator is a fresh `CHALLENGE_TYPE` constant set in the Action's `EventActionTriggered` handler, then read by the Risk's `handleEvent` on the challenge event:

```php
// Action_NNNNN::handleEvent on EventActionTriggered
$event->theah->game->globals->set(Game::CHALLENGE_TYPE, Game::MY_NEW_CHALLENGE_TYPE);

// _NNNNN::handleEvent on EventChallengeRejected / EventCharacterIntervened
$game = $event->theah->game;
if ($event instanceof EventChallengeRejected
    && $game->globals->get(Game::CHALLENGE_TYPE) == Game::MY_NEW_CHALLENGE_TYPE)
{
    // ... apply side effect to $event->targetId
}
```

Reference: `_03021` (Cornered) — `CORNERED_CHALLENGE_TYPE` is consumed for correlation only; `Theah::interventionCheck` doesn't branch on it (gates stay normal).

### "Your performer" semantics

When the printed text says "Your performer issues a challenge," the framework picks the performer first via `RequiresPerformerSelected = true`. The chosen performer's id is in `$game->globals->get(Game::CHOSEN_PERFORMER)` by the time `isValidTargetForAbility` runs.

Override `getPerformersForAction` to filter the candidate list (must be in city, must `canChallenge()`, must have at least one valid target). The base `RiskCityAction::getPerformersForAction` already filters to city characters; layer your predicates on top.

#### `canChallenge()` does NOT check `Engaged`

`Character::canChallenge()` is `return $this->isControlled();` — nothing more. Most challenge-issuing Risks (`_01083`, `_03008`) don't impose an engage cost on the performer, so the base check is sufficient.

When the printed text begins with **"Engage your performer"** (or otherwise imposes Engagement as a cost on the chosen performer), an already-engaged performer cannot pay the cost and must be filtered out at both the availability and performer-list level. Layer `! $p->Engaged` on top of `canChallenge()` in **both** `isAvailableToPlayer` and `getPerformersForAction`:

```php
$characters = array_filter($characters, fn(Character $c) => $c->canChallenge() && ! $c->Engaged);
```

Reference: `Action_03021` (Cornered). The same rule applies to any non-City Action whose text engages the performer as a cost — the engage-already-engaged predicate goes wherever you'd normally just check `canChallenge()`.

**When to pay the engage cost:** if a later sub-state still needs the player to pick a target, queue `createCardEngagedEvent` on `EventActionTriggered` *before* the transition to the chooser (cost paid at announcement). Mirror `Action_03021`, `Action_03030`, `Action_03034`. Only defer engage into `actFromActionWithId` when the engage and the effect resolve in the same atomic confirm (e.g. `Action_02051` engage-performer + engarde-target together).

### Pattern A.1 — City Action that moves a chosen character (enemy OR friendly)

For City Actions like "Target an adjacent enemy character • Move them …" (`_01115` Taunt) and "Move your adjacent Thug or Bodyguard to this location" (`_03011` Provoking the Pack), the shape is identical except for who you target:

1. **`RiskCityAction implements IAbilityThatTargetsCharacters`**, `RequiresPerformerSelected = true`. Mark the **Risk class itself** with `IRiskThatTargetsCharacters`.
2. **Performer filter** in `isAvailableToPlayer` + `getPerformersForAction`: the player's city characters with at least one valid target at an adjacent location (use `getAdjacentCityLocations(..., $includeHome = true)`).
3. **`isValidTargetForAbility`** branches on enemy vs friendly:
   - **Enemy-target:** `$character->ControllerId == $performer->ControllerId` → reject ("you cannot move your own character"). See `Action_01115`.
   - **Friendly-target:** `$character->ControllerId != $performer->ControllerId` → reject ("you may only move one of your own characters"). See `Action_03011`. Layer trait predicates (`hasTrait("Thug") || hasTrait("Bodyguard")`) on top.
   - Always: target's location must be in the performer's adjacent-locations set.
4. **`handleEvent` on `EventActionTriggered`** queues `createTransitionEvent(..., "NNNNN", $this->Id)` to a card-specific GameState class sub-state. State id `4<NNNNN>`; named transition `"targetChosen" => HIGH_DRAMA_PLAYER_TURN_EVENTS` (and `"zombie"`). Possible action: `actFromCardWithId` (single character id, NOT a location). The JS confirm button is `actChooseCardSelected` + `onChooseInPlayCardConfirmed`.
5. **`actFromActionWithId`** validates via `isValidTargetForAbility`, queues `createCardMovingEvent(...)` + `createActionResolvedEvent(...)`, then `$game->gamestate->nextState("targetChosen")`.

The card-specific sub-state is needed (you can't use the shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`) because no challenge is being issued — the shared chooser drives the challenge flow.

### Pattern A.2 — Wound your performer • Move them to any location • Mandatory extra action (same performer)

For City Actions like "Wound your performer • Move them to any location, then they may perform another action" with the italic *(It must be performed and they must be the performer of the action)* — see `_03032` Bloody Entrance.

**Action shape** (`RiskCityAction implements ISorcererAbility` when Sorcerer-prefixed):

1. **`RequiresPerformerSelected = true`**. Filter performers in `getPerformersForAction`: Sorcerer trait (when text says Sorcerer) + at least one valid destination.
2. **Destination list** — all city location names + `LOCATION_PLAYER_HOME` if the performer is not already at Home. Exclude the performer's current location. Copy `Action_03029::getValidDestinationLocations` / `Action_03032` — this is the "any location" pool, **not** `getAdjacentCityLocations`.
3. **Sub-state** — location chooser GameState (`State_highDramaPhaseNNNNN`), same wiring as Pattern B.1 / `03009`: `actFromCardWithLocations` → `actFromActionWithIds`, named transition `"locationChosen"`.
4. **Effect order in `actFromActionWithIds`:**
   - `createSorcererAbilityStartEvent` (if Sorcerer)
   - `createCharacterBeingWoundedEvent` on the performer — call `$theah->eventCheck($woundEvent)` before `queueEvent`
   - `createCardMovingEvent` for the performer — call `$theah->eventCheck($moveEvent)` before `queueEvent`
   - `createSorcererAbilityPlayedEvent` (if Sorcerer)
   - **Grant follow-up turn:**
     ```php
     $game->globals->set(Game::EXTRA_ACTIONS, 1);
     $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $performer->Id);
     ```
   - `createActionResolvedEvent` + `nextState("locationChosen")`

**WHY two globals:** `EXTRA_ACTIONS` is consumed in `stNextPlayer` to keep the same *player* active. `CHOSEN_PERFORMER` is wiped at the start of every `stNextPlayer` along with other action globals — it cannot carry the lock across the extra-action boundary. `EXTRA_ACTION_PERFORMER` survives until the turn actually advances to the next player (cleared in the `else` branch of `stNextPlayer` when `EXTRA_ACTIONS == 0`).

**Framework enforcement** (already wired — do not reimplement per card):

| Layer | What it does |
|---|---|
| `Game::getExtraActionPerformerId()` / `mustPerformExtraAction()` / `assertIsExtraActionPerformer()` / `filterPerformerIdsForExtraAction()` | Helpers on `Game.php` |
| `Theah::characterCanMove/Recruit/Equip/BasicChallenge/BasicClaim()` | Single-character versions of the basic-action availability checks |
| `Theah::actionAvailableToPerformer()` / `playerHasInPlayActionsForPerformer()` / `playerHasInHandActionsForPerformer()` | Filter card actions to those the locked character can perform |
| `ArgumentsTrait::argPlayerTurn()` | When locked: `mustPerformAction=true`, recompute each `can*` for that character, hide brutes |
| All performer-chooser args | `filterPerformerIdsForExtraAction()` → only the locked id |
| All `actHighDrama*PerformerChosen` | `assertIsExtraActionPerformer($id)` |
| `actHighDramaPass()` | throws when `mustPerformExtraAction()` |
| `OnUpdateActionButtons.js` | hides Pass when `args._private.mustPerformAction` |

**Card-side only:** set both globals when the effect resolves. No additional framework edits needed for future cards that reuse this pattern.

Prior art for `EXTRA_ACTIONS` without performer lock: `Action_01090`, `Action_01139`, `Action_01154`, `Action_01124`, `Action_03013` — those grant extra actions where any character (or pass) is fine.

### Pattern A.3 — Engage performer • En garde another friendly • may heal / if not draw

For City Actions like **"Diplomat City Action: Engage your performer • En garde another character you control at this location. Then, that character may heal a wound. If they do not, draw a card."** — see `_03034` La Voix des Sans Voix.

Composition of existing pieces (do not invent a new ability file type):

1. **`RiskCityAction implements IAbilityThatTargetsCharacters`**, `RequiresPerformerSelected = true`. Mark the Risk with `IRiskThatTargetsCharacters`.
2. **Performer filter:** `hasTrait("Diplomat")` (or whatever trait prefix the heading uses) **and** `! Engaged` (engage cost) **and** at least one valid En Garde target at the same location.
3. **`isValidTargetForAbility` (friendly same-location En Garde):**
   - Reject the performer (`Id == CHOSEN_PERFORMER`) — text says "another".
   - Reject non-controlled / wrong controller.
   - Same location as performer.
   - **`$character->Engaged` must be true** — "En garde" verb only applies to engaged characters (`createCardEngardedEvent`). Mirror `Action_02051` / skill item 16.
4. **`EventActionTriggered`:** queue `createCardEngagedEvent` on the performer, then `createTransitionEvent(..., "NNNNN")` into the character chooser. Do **not** wait until target confirm to pay the engage cost when a chooser follows.
5. **`actFromActionWithId` (chooser state):** validate target → `createCardEngardedEvent` on the target → stash `CHOSEN_TARGET` → then branch:
   - **`Wounds > 0`:** queue transition `"NNNNN_2"` into a heal-or-draw sub-state.
   - **`Wounds == 0`:** they cannot heal → queue `createCardDrawnEvent` + `createActionResolvedEvent` immediately (mirror `Action_01049`'s already-engaged auto-wound when the "may" option is impossible).
6. **Second state (`NNNNN_2`) — same-player "may X / if they do not, Y":** two explicit `actFromCardWithId` buttons `{id:1}` / `{id:2}` (heal / draw). Prefer labeled positive buttons over Pass when the alternate branch is itself an effect ("draw a card"), not a skip. Wire JS like `highDramaPhase01049_2`. Call `createActionResolvedEvent` after either branch.

**WHY two GameState classes:** chooser → EVENTS (so engage/engarde flush) → optional heal/draw prompt. Both transitions go through `HIGH_DRAMA_PLAYER_TURN_EVENTS` (`"targetChosen"` / `"done"` → EVENTS; `"NNNNN"` / `"NNNNN_2"` entries in the EVENTS map).

**JS trio:** state 1 mirrors `highDramaPhase03011` (performer highlight + selectable ids + Confirm); state 2 mirrors `highDramaPhase01034_2` highlights + two action buttons.

References: `Action_03034`, `Action_02051` (en garde targets), `Action_01049_2` / `Technique_02054` (may / if they do not buttons), `Action_03021` (engage-at-announcement).

### Pattern A.4 — Opposing target • they claim (controller) • you move Renown

For City Actions like **"Target an opposing character • If their controller does not control this location, they claim it and you move a Renown from this location to another."** — see `_03056` Astute.

**Pronoun trap (load-bearing):** the same clause says **they** claim and **you** move. **They** = the targeted character's controller (claim via that character as `performerId` on `createLocationClaimedEvent`). **You** = the active player (Renown move). Do **not** "helpfully" rewrite into you-claim — that fights the printed grammar and turns a devil's-bargain Cunning effect into a free steal. "Does not control" includes uncontrolled, you-controlled, and third-party-controlled — the opponent can steal *your* claim.

**Action shape** (`RiskCityAction implements IAbilityThatTargetsCharacters`, `RequiresPerformerSelected = true`):

1. Mark the Risk with `IRiskThatTargetsCharacters`.
2. **Valid target** (all required — gate availability and `isValidTargetForAbility` the same way):
   - Opposing (`ControllerId` ≠ performer, ≠ 0) at the performer's location ("this location").
   - `getControllerForLocation($location) != $character->ControllerId` — the printed "if" clause.
   - `canLocationBeClaimedBy($character->ControllerId, $location)` — Leshiye / Indomitable Will etc. (central API; `$playerId` reserved for future rules).
   - Location `Renown >= 1` — "and you move a Renown" is half the effect; no Renown = free claim with no payoff. Gate both halves together.
3. **Two GameStates** (same EVENTS sandwich as A.3):
   - `NNNNN` — character chooser (`actFromCardWithId`, `"targetChosen"` → EVENTS). JS = `highDramaPhase03011` shape.
   - `NNNNN_2` — Renown destination (`actFromCardWithLocations` → `actFromActionWithIds`, `"locationChosen"` → EVENTS). JS = `highDramaPhase03045` city-only location chooser (not Home). Destinations = all other city locations.
4. **`actFromActionWithId` (state 1):** validate → stash `CHOSEN_TARGET` + **`CHOSEN_LOCATION` = source** (WHY: claim reactions / board shifts can move pieces before the Renown chooser; source must survive) → queue `createLocationClaimedEvent($target->ControllerId, $target->Id, $location)` → queue transition `"NNNNN_2"`. If claim became illegal between availability and confirm, notify + `createActionResolvedEvent` and skip the Renown state (mirror `Action_03053` / `Action_01130`). If Renown somehow hits 0 after claim, skip `_2` and resolve.
5. **`actFromActionWithIds` (state 2):** Moving + Removed + Added Renown events with shared `batchId` (`$isMove = true` on Added). Same producer idiom as `Reaction_03041` / `Action_01117`. Then `createActionResolvedEvent`.

**WHY not shared challenge chooser:** no challenge is issued — need a card-specific character GameState, then a separate location GameState for the Renown half.

References: `Action_03056`, `Reaction_03041` (Renown move batch), `Action_03053` / `Action_01130` (claim-illegal notify + resolve), `Action_03011` / `Action_03045` (JS shapes).

### Common precondition predicates

A few wordings recur often:

- **"If your performer is opposed":** there is at least one opposing character at the performer's location.
  ```php
  count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) > 0
  ```
  `getOpposingCharactersAtLocation` already filters via `isNotControlledByPlayer` which excludes uncontrolled — satisfies the "opposing = different controller AND controlled" memory feedback automatically. See `Action_03011`.
- **"Your adjacent X":** any of the player's characters with trait/property X at a location in `getAdjacentCityLocations($performer->Location, $includeHome = true)`. The `$includeHome = true` is generally correct when scanning for friendly home-pool characters; for "move TO an adjacent location" use `$includeHome = false` (you don't move someone *to* home from a city slot).

