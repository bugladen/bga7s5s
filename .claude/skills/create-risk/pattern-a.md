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

`Game::NORMAL_CHALLENGE_TYPE` is the default and works for any "target-only" restriction (the Influence gate in `_03008`, for example). Add a new challenge-type constant in `Game.php` when **any** of:

1. **Intervention or refusal *gates* differ from normal** — "Only Leaders can intervene" (`LEGENDARY_REPUTATION_CHALLENGE_TYPE` in `_01083`), "Only characters with 3 Finesse or more may intervene or refuse" (`AJA_CHALLENGE_TYPE`). The framework reads CHALLENGE_TYPE in `Theah::interventionCheck` to enforce these gates.
2. **Intervention or refusal carries a side effect attached to the issuing card** — "If they refuse, engage them" + "Wound any character that intervenes" (`CORNERED_CHALLENGE_TYPE` in `_03021`). The gates themselves stay normal (anyone can refuse or intervene), but the **Risk class needs a correlator** to tell "this challenge is mine" inside its `EventChallengeRejected` / `EventCharacterIntervened` handlers.
3. **An irreversible cost was paid in a card-specific sub-state before the shared choose-target step** — attachment Engage before target pick (`NO_MORE_WORDS_CHALLENGE_TYPE` in `_04019`). `OnUpdateActionButtons.js` shows Back on `highDramaChallengeActionChooseTarget` **only** for `NORMAL_CHALLENGE_TYPE`; a custom type hides Back after the cost. If the paid cost was **attachment** Engage (not performer), still add the type **to** `stIssueChallenge`'s auto-engage list so the performer engages on issue. Also guard `FrameworkActionsTrait::actBack`. See Pattern B.6.

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

#### Dual purpose when the card also prints "Engage your performer"

`stIssueChallenge` auto-engages the performer only for `NORMAL_CHALLENGE_TYPE`, `SERVO_SCARPA_CHALLENGE_TYPE`, `TORVO_ESPADA_CHALLENGE_TYPE`, and `AJA_CHALLENGE_TYPE`. When the Action pays the engage cost itself on `EventActionTriggered` (printed "Engage your performer"), the fresh `CHALLENGE_TYPE` must stay **off** that list — otherwise the performer is engaged twice (second `EventCardEngaged` can re-trigger reactions). So the same constant often serves **both** correlator and "keep off auto-engage." Mirror `Action_03021` (Cornered), `Action_03042` (When Least Expected), `Action_03057` (Censure).

Contrast: `_03008` (Arrogant) uses `NORMAL_CHALLENGE_TYPE` because it does **not** engage the performer as a cost — the auto-engage *is* the challenge's engage.

#### Non-Combat challenge stats

Set `Game::CHALLENGE_STAT` to `STAT_INFLUENCE` / `STAT_FINESSE` / `STAT_COMBAT` to match the printed bracket. For **Influence** challenges, also filter performers with `! $c->DashedInfluence` in both availability and the performer list — a dashed Influence character cannot meaningfully issue that challenge. Mirror `Action_01033`, `Action_02028`, `Action_03037`, `Action_03057`.

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

### Pattern A.5 — Engage + [Stat] challenge + if refused, claim (auto)

For City Actions like **"Engage your performer • Issue an [Influence] challenge to target opposing character. If the challenge is refused, claim your performer's location."** — see `_03057` Censure.

Composition of Pattern A challenge + correlator side-effect (not a new chooser flow):

1. **`RiskCityAction implements IAbilityThatTargetsCharacters`**, `RequiresPerformerSelected = true`. Mark the Risk with `IRiskThatTargetsCharacters`.
2. **Performer filter:** `canChallenge()` **and** `! Engaged` (engage cost) **and** `! DashedInfluence` when the printed challenge is Influence (or the matching `DashedX` for other stats) **and** at least one opposing character at the performer's location.
3. **`EventActionTriggered`:** queue `createCardEngagedEvent` on the performer, set a fresh `CHALLENGE_TYPE` (correlator **and** off the auto-engage list), set `CHALLENGE_STAT` to the printed stat, transition `"NNNNN"` → shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`. No card-specific GameState / JS.
4. **Refuse side effect on the Risk class** (`EventChallengeRejected` + `CHALLENGE_TYPE` match):
   - Resolve the performer via `$event->challengerId` (not `CHOSEN_PERFORMER` — may have shifted).
   - Gate with `cardInCity($challenger)` and `canLocationBeClaimedBy($challenger->ControllerId, $location)`.
   - Queue `createLocationClaimedEvent($challenger->ControllerId, $challenger->Id, $location)`.
   - If unclaimable: notify and skip — do not throw.

**Mandatory vs optional claim:** "If the challenge is refused, claim …" (no *may*) is Forced-style on the Risk — **not** a `RiskReaction`. Contrast `Reaction_03005` (Red Hand), which is an optional "you may claim" Reaction after refuse. Do not invent a Reaction chooser for mandatory claim.

**Do not gate Action availability on claimability.** The challenge is still a live play when you already control the location or Leshiye / Indomitable Will blocks claim — only the refuse bonus fails. Gate the *emit* with `canLocationBeClaimedBy`, not `isAvailableToPlayer`.

References: `_03057` / `Action_03057`, correlator section above, `_03021` (engage + side-effect type), `Reaction_03005` (optional claim Reaction — different shape).

### Pattern A.6 — Duelist (trait) City Action • headcount If • Combat challenge

For City Actions like **"Duelist City Action: Target an opposing character • If their controller has more characters at this location than you, your performer issues a [Combat] challenge to that character."** — see `_03058` Courageous.

Composition of Pattern A challenge + trait prefix + bullet-**If** as **target filter** (not a post-target branch):

1. **`RiskCityAction implements IAbilityThatTargetsCharacters`**, `RequiresPerformerSelected = true`. Mark the Risk with `IRiskThatTargetsCharacters`.
2. **Performer filter:** `hasTrait("Duelist")` (or whatever trait the heading prints) **and** `canChallenge($theah)` **and** at least one valid target. No `! Engaged` unless the text also prints an engage cost (contrast A.5 / Cornered).
3. **Valid target** (gate availability and `isValidTargetForAbility` the same way):
   - Opposing (`ControllerId` ≠ performer, ≠ 0) at the performer's location.
   - **Headcount If:** `count(getCharactersAtLocationByPlayerId($location, $target->ControllerId)) > count(getCharactersAtLocationByPlayerId($location, $performer->ControllerId))` — strict `>` for "more … than". "You" = the acting player's characters at that location, not a global in-play count.
4. **`EventActionTriggered`:** `NORMAL_CHALLENGE_TYPE` + `STAT_COMBAT` (or the printed bracket) + transition `"NNNNN"` → shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`. No card-specific GameState / JS.
5. **No custom `CHALLENGE_TYPE`** — there is no refuse/intervene side effect attached to this card. Auto-engage from `stIssueChallenge` is correct (no printed engage cost). Contrast A.5 / Cornered.

**Bullet-If discipline (shared with A.4):** when text is `Target … • If <condition>, <effect>`, treat the If as a **target-availability filter** so the Action is only offered when the effect can fire. Do not let the player target freely and then silently no-op the challenge. Same idea as Astute gating on "does not control this location" before claim.

**Duelist is a trait gate, not Sorcerer** — `hasTrait("Duelist")` only; do not `implement ISorcererAbility`.

References: `_03058` / `Action_03058`, `Action_03008` (shared Combat challenge chooser), `Action_02061` (Duelist performer gate), A.4 (bullet-If as filter).

### Pattern A.7 — "You may engage your performer, if you do, ignore all costs" + effect

For City Actions / Actions like **"Sorcerer City Action: You may engage your performer, if you do, ignore all costs • Heal two wounds from another character at this location."** — see `_03060` (Matushka's Song). Same pay-time cost channel as `_01133` (Matushka's Efficiency — plain Action + move), but Song uses a **GameState** for the engage choice instead of a RiskReaction.

Composition (do not invent a new cost channel):

1. **`public bool $WillEngage = false`** on the **Risk class**. Reset to false when entering the engage-choice path.
2. **Pre-pay GameState (preferred for new cards):** on `EventEnteringPayState` for this card in hand, reset `WillEngage`, and if the performer is **not** Engaged, `stackEvent` a `createTransitionEvent(..., "NNNNN_2", ...)`. Wire `"NNNNN_2"` under **`HIGH_DRAMA_IN_HAND_ACTION_EVENTS.transitions`** (not `HIGH_DRAMA_PLAYER_TURN_EVENTS` — that is post-pay). State transitions **`"done"` / `"zombie"`** → `HIGH_DRAMA_IN_HAND_ACTION_EVENTS` (no empty `""` when more than one transition). Engage → set `WillEngage`, `ABNORMAL_FLOW`, queue `createCardEngagedEvent`, then **`calculateInHandPayDiscount(...)`**, then `nextState("done")`. Pass → set **`ABNORMAL_FLOW`** then `nextState("done")` with WillEngage still false. Already-engaged performers skip the state (cannot pay the optional cost) but can still play paying full Wealth. **Both Engage and Pass must set `ABNORMAL_FLOW`** — otherwise pay shows stock Back → `backPerformer`, which re-enters `EnteringPayState` and re-prompts the engage chooser.
3. **Legacy Reaction shape (`_01133` only unless Eddie asks):** pay-state `RiskReaction` Engage/Pass on `EventEnteringPayState` — same WillEngage semantics. Prefer the GameState for new work. (01133 does **not** currently recalc discount after Engage — same footgun; fix Song-style if Efficiency ever regresses.)
4. **`getActionFromHandDiscount` on the Action:** when `$action->Id == $this->Id` and `$owner->WillEngage`, add `$owner->WealthCost` (printed "ignore all costs" = waive this Risk's Wealth). Gate on `$action->Id` so a sticky flag cannot discount unrelated hand Actions.
5. **Effect** is a normal Pattern A / B chooser after pay (heal character, move, …). Sorcerer prefix → `ISorcererAbility` + start/played around the effect.
6. **Heal chooser without printed "Target":** "Heal … from another character at this location" is still a character chooser in the UI, but do **not** `implements IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters` — Rules Team + Cesca (`Reaction_01008`) require the printed word "target" (or similar). No Cesca whitelist/`copyCard` for abilities that lack that wording. Validation stays a private helper (e.g. `isValidHealCharacter`), not `isValidTargetForAbility`.
7. **Heal pool:** "another character at this location" with no "you control" → any other character at the performer's location with `Wounds > 0` (mirror `Action_01091`'s unrestricted heal pool; exclude the performer for "another").

**WHY recalculate discount on Engage (load-bearing):** `EventEnteringPayState` has `runEventHubAfterCards = true`. Cards stack the engage Transition first; EventHub then `stackEvent`s `CalculatePayDiscount`, which gets a *lower* priority and runs **before** the Transition — with `WillEngage` still false. Stacking the Transition does **not** pre-empt the discount. After the player Engages, call `calculateInHandPayDiscount` (mirror `Reaction_01116b` / `Reaction_03013`) so `Game::DISCOUNT` reflects WealthCost. Pass needs no recalc (0 is correct).

**WHY not Action-side engage-at-announce:** the choice must land *before* wealth is locked in the pay UI.

**City vs plain:** "City Action" → `RiskCityAction` (city performers only). "Action" → `RiskAction` (home eligible) — `_01133`.

References: `_03060` / `Action_03060` / `State_highDramaPhase03060_2` (GameState engage + recalc), `_01133` / `Action_01133` / `Reaction_01133` (legacy Reaction engage). (Cesca copies Efficiency `_01133`, not Song — Song has no printed "target".)

### Pattern A.8 — Leader City Action: wound • location-count If • choose-stat pressure • claim on success • locker

For City Actions like **"Leader City Action: Wound your performer • If you control fewer locations than an opponent, pressure your performer's location with your choice of [Combat], [Finesse], or [Influence]. If successful, claim this location. Send this card to The Locker."** — see `_03067` (Ambitious).

Composition of Leader performer + wound cost + bullet-If availability + pressure pipeline + Unique locker (do not invent a new pressure channel):

1. **`RiskCityAction`** — Leader must be in the city. Do **not** set `RequiresPerformerSelected` (Leader is fixed — same as `Action_03020`). No `IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters` — no printed "Target"; the only chooser is Combat/Finesse/Influence buttons.
2. **Availability gates (all required):**
   - `getLeaderByPlayerId` non-null + `cardInCity($leader)`.
   - **Location-count If:** count city locations where `$location->Controller == $playerId`; require **exists** an opponent with a strictly greater count (`$myCount < $oppCount`). "Fewer … than an opponent" ≠ fewer than every opponent / ≠ fewest overall.
   - Leader `canPressure` at least one of Combat / Finesse / Influence (dashed stats are ineligible).
   - Do **not** gate on `canLocationBeClaimedBy` — claim is the success payoff; pressure still plays when Leshiye / Indomitable Will blocks (same discipline as A.5 / `Action_01141` / `Action_01206`).
3. **`EventActionTriggered`:** set `CHOSEN_PERFORMER` to the Leader id yourself (pressure/`stHighDramaPressureLocation` reads it; pay path left it unset because `RequiresPerformerSelected` is false) → `createCharacterBeingWoundedEvent` on the Leader (`eventCheck` before `queueEvent`) → `createTransitionEvent(..., "NNNNN")` into the choose-stat GameState. Wound is a printed cost paid **before** the chooser (same announce-before-chooser discipline as engage-before-chooser).
4. **Choose-stat GameState** (`State_highDramaPhaseNNNNN`, id `4NNNNN`): buttons for each pressureable stat (`id` 1/2/3 → `STAT_COMBAT` / `STAT_FINESSE` / `STAT_INFLUENCE`). JS: `OnUpdateActionButtons` offers only stats returned in `args.stats`; `OnEnteringState` highlights the Leader. Named transition `"statChosen"` → `HIGH_DRAMA_PLAYER_TURN_EVENTS`.
5. **`actFromActionWithId` (stat chosen):** validate `canPressure` + location-count If still true → set `PRESSURING_PLAYER`, `PRESSURE_TYPE = NORMAL`, `PRESSURE_STAT` → `createPressureOccuringEvent` + `createTransitionEvent(..., "pressureLocation", $this->Id)` → `nextState("statChosen")`. The 4th arg on the pressure transition is the ability id that becomes `TRANSITION_INTERNAL_ID` → `EventLocationPressured.abilityId` → `EventLocationPressureResult.abilityId`.
6. **`EventLocationPressureResult` + `$event->abilityId == $this->Id`:**
   - On `$event->success`: if `canLocationBeClaimedBy`, queue `createLocationClaimedEvent($event->playerId, $event->performerId, $event->location)`; else notify and skip (do not throw).
   - **Always** queue `createCardSentToLockerEvent` for the Risk, then `createActionResolvedEvent`. WHY always: Unique spend — playing the Action removes the card whether pressure succeeds or fails. Do not success-gate the locker solely because the sentence follows "If successful, claim…".
7. **Contrast A.5:** A.5 is challenge + claim-on-**refuse**. A.8 is pressure + claim-on-**success**. Do not mint a `CHALLENGE_TYPE`; do not listen to `EventChallengeRejected`.

**Pressure pipeline reminders (shared with `Action_01141` / `Action_01206` / `Action_03040`):**
- `stHighDramaPressureLocation` reads `PRESSURE_STAT` (default Influence) and `CHOSEN_PERFORMER` (or `CHOSEN_LOCATION` when performer is 0).
- Hand-played Risks are discarded via `createCardDiscardedFromHandEvent` after `ActionTriggered` is queued — locker later moves the card from discard to locker; `createCardSentToLockerEvent` alone is enough (no separate remove-from-discard required).

References: `_03067` / `Action_03067` / `State_highDramaPhase03067`, `Action_01141` / `Action_01206` (pressure → claim on success), `Action_03020` (Leader, no performer chooser), A.5 (claimability gate discipline — different trigger).

### Pattern A.9 — Opponent-controlled location • Engage an opposing character

For City Actions like **"City Action: If this location is controlled by an opponent • Engage an opposing character."** — see `_03071` (Leverage). Heroic mirror (you control → En garde *your* performer, no chooser): `_01159` Appealing to the People.

Composition of bullet-If claim-control + engage opposing chooser (do not invent a challenge or Cesca target):

1. **`RiskCityAction`**, `RequiresPerformerSelected = true`. **No** `IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters` — printed text says "Engage an opposing character", not "Target". Private helper (e.g. `isValidEngageCharacter`) + GameState character chooser (`State_highDramaPhaseNNNNN`, JS = `highDramaPhase03011` / `03060` trio).
2. **Performer filter** (availability + `getPerformersForAction` — bullet-If is an availability filter, same discipline as A.4 / A.6):
   - **Location controlled by an opponent:** `getControllerForLocation($performer->Location) != 0 && != $performer->ControllerId` (same claim-control sense as Pattern B.1 / `_03045`, applied to the *current* location rather than a move destination).
   - **≥1 valid engage target** at that location (below).
3. **Engage pool:** `getOpposingCharactersAtLocation` filtered to **`! Engaged`**. Already-engaged characters cannot pay an Engage effect; EventHub's `EventCardEngaged` would only re-notify. Re-check the location If in the private validator so board shifts between announce and confirm still fail cleanly.
4. **`EventActionTriggered`:** `createTransitionEvent(..., "NNNNN")` into the chooser. **`actFromActionWithId`:** validate → `createCardEngagedEvent` on the chosen opposing character → `createActionResolvedEvent` → `nextState("targetChosen")`.

**Contrast:**
- A.3 Engarde targets = *your* Engaged characters (`createCardEngardedEvent`). A.9 Engage targets = *opposing* non-Engaged (`createCardEngagedEvent`).
- `_01159` "En garde your performer at a location you control" resolves on announce with no chooser (performer is the subject). A.9 needs a chooser because the subject is an opposing character.
- Pattern B.1 is "move *to* an adjacent opponent-controlled location." A.9 is "you are already *at* an opponent-controlled location."

References: `_03071` / `Action_03071` / `State_highDramaPhase03071`, `_01159` / `Action_01159` (control gate + engarde self), Pattern B.1 (`getControllerForLocation`), A.4/A.6 (bullet-If as filter), `_03060` (no-Target character chooser).

### Pattern A.10 — Target opposing • Destroy all engaged attachments • Engage remaining

For City Actions like **"City Action: Destroy all engaged attachments equipped to target opposing character. Then, engage each of their equipped attachments."** — see `_03072` (Sabotage). Choose-one destroy of an engaged attachment on the adversary (duel Technique, not City Action): `Technique_02026b`.

1. **`RiskCityAction`**, `RequiresPerformerSelected = true`, **`IAbilityThatTargetsCharacters`** + Risk **`IRiskThatTargetsCharacters`** — printed **"target"**. Chooser GameState + faf JS trio (`03011` / `03056` / `03071` shape).
2. **Target filter:** opposing at performer location with ≥1 non-`FakeAttachment` attachment (either destroy half or engage half is useful).
3. **On confirm — snapshot before queue:** split real attachments into `$toDestroy` (`Engaged`) and `$toEngage` (`! Engaged`). WHY snapshot: unequip/discard events are queued, not applied mid-act — re-reading `Attachments` would still include doomed cards. For each destroy: `createAttachmentUnequippedEvent` (`eventCheck`) + `createCardDiscardedFromPlayEvent(..., $asEffect = true)` (`OwnerId`, location from attachment). Then `createCardEngagedEvent` on each remaining (the unengaged snapshot). Skip FakeAttachment always.
4. No challenge / `CHALLENGE_TYPE`. Maneuver half on the same card (destroy all engaged on adversary, pure resolve) mirrors the destroy loop without a chooser — see `Maneuver_03072`.

References: `_03072` / `Action_03072` / `State_highDramaPhase03072` / `Maneuver_03072`, `Technique_02026b` (choose-one engaged destroy), `Action_03038b` (unequip+discard destroy), contrast A.9 (engage *character*, no Target / no Cesca).

### Common precondition predicates

A few wordings recur often:

- **"If your performer is opposed":** there is at least one opposing character at the performer's location.
  ```php
  count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) > 0
  ```
  `getOpposingCharactersAtLocation` already filters via `isNotControlledByPlayer` which excludes uncontrolled — satisfies the "opposing = different controller AND controlled" memory feedback automatically. See `Action_03011`.
- **"Your adjacent X":** any of the player's characters with trait/property X at a location in `getAdjacentCityLocations($performer->Location, $includeHome = true)`. The `$includeHome = true` is generally correct when scanning for friendly home-pool characters; for "move TO an adjacent location" use `$includeHome = false` (you don't move someone *to* home from a city slot).
- **"If you control fewer locations than an opponent":** count `$location->Controller == $playerId` over `getCityLocations()`; require **exists** an opponent with a strictly greater count. Pattern A.8 / `_03067`.
- **"If this location is controlled by an opponent":** `getControllerForLocation($performer->Location) != 0 && != $performer->ControllerId`. Pattern A.9 / `_03071` (performer filter); same helper as Pattern B.1 destinations.
