---
name: create-risk
description: Implement or finish a Risk card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Risk). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Risk, or when they reference a card whose class extends Risk and has unimplemented Text. Triggers on phrases like "implement this risk", "finish _NNNNN" (when it extends Risk), "wire up the maneuver", "add the city action on this risk", or natural-language descriptions of a Risk card (faction-deck combat card with Riposte/Parry/Thrust, played as a maneuver during duels, sometimes carries a City Action / Action / Reaction).

---

# Creating a Risk

This skill covers cards that extend `Risk` — the faction-deck cards a player draws into hand and plays as combat cards during duels. They are **not** city-deck cards; each player's faction deck mixes Risks with Characters/Schemes/etc. A Risk has Riposte/Parry/Thrust combat stats and may carry one or more of: a Maneuver (duel-round modifier), a City Action (played from city), an Action (played from hand or in play), or a Reaction.

Canonical references (read at least the ones that match your card shape before writing code):

- `modules/php/cards/_7s5s/_01083.php` (Legendary Reputation) — **RiskCityAction issuing a Combat challenge** with custom challenge type (Only Leaders can intervene). The exemplar for "Your performer issues a [X] challenge" Risks.
- `modules/php/cards/_7s5s/_01084.php` (Master of Valroux Style) — **Duelist Maneuver** (+1 Riposte + draw card + adversary penalty). Maneuver isAvailable gated on `hasTrait('Duelist')`. Combat-card discount when adversary engaged.
- `modules/php/cards/_7s5s/_01115.php` (Taunt) — **Risk with both a City Action and a Maneuver.** Action moves an adjacent opposing character; Maneuver gates on `actor->ModifiedFinesse > adversary->ModifiedFinesse` and queues a transition for the adversary's controller to pick a hand card to discard.
- `modules/php/cards/faf/_03011.php` (Provoking the Pack) — **Friendly-character variant of the move-an-adjacent-character pattern, plus a "control trait at duel location" Gambling Maneuver.** City Action gated on "performer is opposed", target is one of the player's own Thug/Bodyguard at an adjacent location. The exemplar when the chooser target is your own character (not an enemy) — same `IAbilityThatTargetsCharacters` interface, with `isValidTargetForAbility` flipped to `ControllerId == performer->ControllerId`.
- `modules/php/cards/_7s5s/_01061.php` (Well-Equipped) — **Risk with a simple Action and a Maneuver** that conditionally draws a card based on equipped Weapon attachments.
- `modules/php/cards/faf/_03008.php` (Arrogant) — **Risk with a City Action (Influence-gated Combat challenge) and a Gambling Maneuver.** Uses `Game::NORMAL_CHALLENGE_TYPE`; Influence gate enforced via `IAbilityThatTargetsCharacters::isValidTargetForAbility`.
- `modules/php/cards/faf/_03009.php` (Follow the Thread) — **Sorcerer Strega Action that moves the performer to an adjacent location filtered by destination contents + Strega Maneuver (-1 Thrust, wound adversary).** Exemplar for "Action:" (not "City Action:") with `parent::getPerformersForAction` (home + city performers), `actFromCardWithLocations` location-chooser sub-state, and the `-1 thrust + wound` maneuver shape.
- `modules/php/cards/faf/_03010.php` (Manipulative) — **Strega Reaction with multi-stage cross-player choice on top of the standard RiskReaction pay state.** Triggers on both `EventApproachCharacterPlayed` and `EventCharacterMustered` (filtered by `$event->fromLocation == LOCATION_APPROACH`). After the framework pays the Risk (discarded from hand), `EventRiskReactionTriggered` chains into a second `createReactionTransitionEvent` to the opposing player, who picks "return + muster different" vs "take the wound". Exemplar for "wound them unless their controller does X" on a RiskReaction (vs the SchemeCityAction shape in `_02036` Crimson Roger).

When in doubt, mirror one of those rather than invent.

> **Sibling skills:** `create-character`, `create-city-character`, `create-city-event-card`, `create-city-attachment`, `create-scheme`. Maneuver/Technique mechanics and challenge-flow plumbing are largely shared with `create-character` — read its **Pattern E** (Techniques and Maneuvers) and the action-base table when wiring a Risk's challenge action.

## Base Anatomy

`Risk extends Card implements IFactionCard, IWealthCost` and brings `FactionCardTrait` + `WealthCostTrait`. It adds combat-card fields (Riposte/Parry/Thrust and their dashed counterparts) which are wired by the `Card` base class.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;        // if any Action
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;        // if any Action
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;      // if any Maneuver
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;      // if any Reaction
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;  // if any ability targets a character
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;      // if any Maneuver
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;      // if any Reaction
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _NNNNN extends Risk implements IHasActions, IHasManeuvers   // mix in as needed
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';   // or '_7s5s' / 'tac'
        $this->ExpansionNumber = 3;
        $this->CardNumber      = N;

        $this->initializeFaction('Vodacce');

        $this->WealthCost = 1;

        // Combat stats — set whichever the printed card shows.
        $this->Riposte       = 2;
        $this->Parry         = 0;
        $this->DashedParry   = true;
        $this->Thrust        = 1;
        // Riposte/Parry/Thrust default to 0; DashedX defaults to false.

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Hubris'),
            clienttranslate('Challenge'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_NNNNN(),
        ];

        $this->Maneuvers = [
            new Maneuver_NNNNN(),
        ];
    }
}
```

Field notes:
- **`initializeFaction(...)` is mandatory** — Risks live in a faction deck.
- **`CardNumber` matches the `NNNNN` in the filename.**
- **`WealthCost` is mandatory** — every Risk has a printed Wealth cost.
- **Combat stats:** set whichever the card shows. Default 0 is fine if the stat is absent. Use `Dashed{Riposte,Parry,Thrust}` for printed-dashed values (the card shows a dashed line meaning "this stat cannot be modified above 0" — handled by the framework when `addParry` etc. is called).
- **Traits must exist in `TraitNames::$TraitsJson`** (`modules/php/TraitNames.php`). Add missing ones in alphabetical order. (Memory feedback.)
- **`IRiskThatTargetsCharacters`:** mark on the Risk class itself (not its Actions/Maneuvers) when any of its abilities targets a character — **including when the target is your own character** (the interface tracks "this Risk hands the player a character chooser", not enemy-only targeting). Compare `_01083`, `_01115`, `_03008`, `_03011`.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code.

| Card phrase | Pattern |
|---|---|
| **`<b>City Action:</b>`** | Pattern A — `RiskCityAction`. The Action lives in `cards/<expansion>/actions/Action_NNNNN.php`. Performer must be in the city (framework helper). |
| **`<b>Action:</b>`** (no "City") | Pattern B — `RiskAction`. Defaults to requiring the Risk in hand (`Card::Location == LOCATION_HAND`); override `overrideInHandCheck` only when the card text implies otherwise. **Performer pool is home + city** — do NOT filter to city characters even when the effect implies city (e.g. "move to an adjacent location"). The keyword "City" in the heading is mechanical: if absent, home performers are eligible too. |
| **`<b>Maneuver:</b>`** / **`<b>Duelist Maneuver:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern C — `Maneuver` subclass in `cards/<expansion>/maneuvers/Maneuver_NNNNN.php`. Trait-prefixed Maneuvers add an `isAvailable` gate (`hasTrait` or `DUEL_GAMBLED`). |
| **`<b>Reaction:</b>`** | Pattern D — `RiskReaction`. Pre-commit hook requires hand-only guard (`Location == Game::LOCATION_HAND`) + `setUsed`/`isAvailable` literal calls. |
| **"While [adversary/condition] …"** (combat-card cost or stat modifier on the Risk itself) | Pattern E — passive on the Risk class. Override `eventCheck` / `handleEvent` directly. See `Maneuver_01084::getManeuverFromCombatCardDiscount` for an in-Maneuver passive (combat-card cost discount). |
| **`<b>Sorcerer …:</b>`** | The ability class (Action/Reaction/Maneuver) additionally `implements ISorcererAbility` — must emit `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). |
| **`<b>Strega …:</b>`** / **`<b>Mercenary …:</b>`** / **`<b>Duelist …:</b>`** | **Mechanical performer-trait gates**, NOT Sorcerer abilities. Enforce via `hasTrait("Strega")` on the chosen performer or `getDuelRoundActor()`. Do NOT `implement ISorcererAbility` for these. Can stack with Sorcerer ("Sorcerer Strega Reaction" is both). |

A single Risk freely combines these. `_01115` has both a City Action and a Maneuver. `_03008` has both a City Action and a Gambling Maneuver. `_01083` is a single City Action only.

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

### Custom challenge type only when intervention/refusal differ

`Game::NORMAL_CHALLENGE_TYPE` is the default and works for any "target-only" restriction (the Influence gate in `_03008`, for example). Add a new challenge-type constant in `Game.php` **only** when intervention or refusal rules differ from normal — e.g., "Only Leaders can intervene" (`LEGENDARY_REPUTATION_CHALLENGE_TYPE` in `_01083`), "Only characters with 3 Finesse or more may intervene or refuse" (`AJA_CHALLENGE_TYPE`). See the existing list in `modules/php/Game.php` for the catalog.

### "Your performer" semantics

When the printed text says "Your performer issues a challenge," the framework picks the performer first via `RequiresPerformerSelected = true`. The chosen performer's id is in `$game->globals->get(Game::CHOSEN_PERFORMER)` by the time `isValidTargetForAbility` runs.

Override `getPerformersForAction` to filter the candidate list (must be in city, must `canChallenge()`, must have at least one valid target). The base `RiskCityAction::getPerformersForAction` already filters to city characters; layer your predicates on top.

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

### Common precondition predicates

A few wordings recur often:

- **"If your performer is opposed":** there is at least one opposing character at the performer's location.
  ```php
  count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) > 0
  ```
  `getOpposingCharactersAtLocation` already filters via `isNotControlledByPlayer` which excludes uncontrolled — satisfies the "opposing = different controller AND controlled" memory feedback automatically. See `Action_03011`.
- **"Your adjacent X":** any of the player's characters with trait/property X at a location in `getAdjacentCityLocations($performer->Location, $includeHome = true)`. The `$includeHome = true` is generally correct when scanning for friendly home-pool characters; for "move TO an adjacent location" use `$includeHome = false` (you don't move someone *to* home from a city slot).

## Pattern B — Action (`RiskAction`)

Use `RiskAction` for in-hand Actions and in-play Actions that aren't City Actions.

```php
class Action_NNNNN extends RiskAction
{
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;
        // ... card-specific preconditions
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            // Resolve the effect inline, OR queue a transition to a sub-state if there's a player choice.
            // ...
            $resolvedEvent = EventFactory::createActionResolvedEvent($event->playerId, $event->actionId);
            $event->theah->queueEvent($resolvedEvent);
        }
    }
}
```

The base `RiskAction::isAvailableToPlayer` enforces "Risk is in hand" unless `$overrideInHandCheck` is true. `RiskAction::getPerformersForAction` adds the player's characters in play to the performer pool — **including characters at the player's Home, not just city characters.** When overriding `getPerformersForAction`, start from `parent::getPerformersForAction(...)` and layer trait/state predicates on top; do NOT swap it out for `getCharactersInCityByPlayerId(...)` just because the effect implies city. A character at home has adjacent city locations and can still perform a "Move to adjacent location" Action.

References: `Action_01061` (Well-Equipped's en-garde-equipped-performer Action), `Action_03009` (Sorcerer Strega Action that moves the performer to an adjacent location).

### Pattern B.1 — "Move your performer to an adjacent location where …"

A common Risk Action shape: the player picks an *adjacent location* meeting some predicate (enemy character at it, available Mercenary at it, claimed by an opponent, etc.). See `Action_03009`. Wire it as:

1. **Filter performers** by trait gates (`Sorcerer`/`Strega`/etc.) and by "has ≥1 valid destination" — `parent::getPerformersForAction(...)` first, then `array_filter`.
2. **`handleEvent` on `EventActionTriggered`:** queue a `createTransitionEvent(..., "NNNNN", $this->Id)` to a card-specific location-chooser sub-state.
3. **`getArgsFromAction`:** in the sub-state, expose `performerId` + `locationIds` (the valid adjacent destinations).
4. **`actFromActionWithIds($ids)`:** `$ids[0]` is the chosen location string (BGA dispatches `actFromCardWithLocations` → `actFromActionWithIds`). Validate it's in the valid list, then queue `createCardMovingEvent($performer->ControllerId, $performer->Id, $performer->Location, $location, $engage = false, $owner->Id, $this->Id)` + `createActionResolvedEvent(...)`. If Sorcerer, bracket with `createSorcererAbilityStartEvent` / `createSorcererAbilityPlayedEvent`.
5. **`$theah->getAdjacentCityLocations($performer->Location, $includeHome = false)`** is the right helper; pass `$includeHome = false` unless the rules text explicitly admits Home as a destination.
6. **`getCharactersAtLocation($location, $includeUncontrolled = true)`** when "available Mercenary" or other uncontrolled-character predicates are part of the destination filter. The default `$includeUncontrolled = false` will silently drop the available mercenaries.
7. **"Available Mercenary"** = `! $character->isControlled() && $character->hasTrait("Mercenary")`.
8. **"Enemy character"** = controlled by an opposing player: `$character->isControlled() && $character->ControllerId != $performer->ControllerId`. Don't conflate with "opposing" (which also requires same location).

## Pattern C — Maneuver

A Maneuver is a Risk-specific ability that activates when the Risk is used as a combat card in a duel round.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_NNNNN extends Maneuver
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah)) return false;
        // ... gating predicates (trait, gambled, stat comparison, etc.)
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            // one-shot side effects (draw a card, wound, transition into a sub-state)
        }
    }
}
```

### Pre-commit hook: EventManeuverCanceled

Every Maneuver subclass must include either an `EventManeuverCanceled` handler OR a literal `EventManeuverCanceled handler not needed` comment. Add the comment when the maneuver has no state to undo (pure additive Riposte/Parry/Thrust + queued draw/etc., framework rolls those back on cancel).

When the maneuver carries state on the Maneuver object (e.g., `Maneuver_01084::IncreaseAdversaryThrust`), include a real handler that clears the flag on cancel.

### "Duelist Maneuver" / "Gambling Maneuver" — trait-prefixed gates

These are **mechanical performer-trait gates**, not Sorcerer abilities. Add an `isAvailable` predicate:

```php
// Duelist Maneuver:
$actor = $theah->getDuelRoundActor();
if (! $actor || ! $actor->hasTrait('Duelist')) return false;

// Gambling Maneuver:
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

`Game::DUEL_GAMBLED` is set true in `FrameworkActionsTrait::actChooseGambleCard` when the gambled combat card is locked in, and cleared in `stDoneRound`. See `Technique_03002` (Aja) for the same gate on the Technique side.

### "If your participant has more <Stat> than the adversary" gate

```php
$actor = $theah->getDuelRoundActor();
$adversary = $theah->getDuelRoundOpponent();
return $actor->ModifiedInfluence > $adversary->ModifiedInfluence;
```

Use **modified** stats (`ModifiedInfluence`, `ModifiedFinesse`, etc.), not the printed base — the comparison must honor live modifiers. Reference: `Maneuver_01115` (Finesse comparison), `Maneuver_03008` (Influence comparison).

### Adding Riposte/Parry/Thrust during calc

`EventDuelCalculateManeuverValues` exposes plain int fields (`$riposte`, `$parry`, `$thrust`) that you mutate directly — unlike `EventDuelCalculateCombatCardStats` which uses `addRiposte`/`addParry`/etc. methods that respect `DashedX` flags.

```php
$event->riposte += 1;
$event->explanations[] = sprintf(
    $event->theah->game->translate("%s adds 1 Riposte."),
    $this->getOwningCard($event->theah)->getInjectCode()
);
```

The calc event can fire multiple times during a single round (recalc on engage state changes etc.) — so put **one-shot** side effects (draw a card, wound, transition) in `EventResolveManeuver`, which fires once.

References: `Maneuver_01061` (conditional draw on equipped Weapon), `Maneuver_01084` (Duelist gate + adversary Thrust bonus next round + combat-card discount when adversary engaged), `Maneuver_01115` (cross-player hand-pick discard via `createTransitionEvent` to the adversary's controller), `Maneuver_03008` (Gambling gate + Influence comparison + Riposte+draw), `Maneuver_03009` (Strega gate + `-1 Thrust` in calc + wound adversary in resolve), `Maneuver_03011` (Gambling gate + "you control a trait X at the duel location" predicate + pure `+1 Riposte` in calc, no Resolve handler).

### Pure-calc maneuvers (no `EventResolveManeuver` needed)

When the maneuver only adds/subtracts stat values and has no one-shot side effect (no draw, no wound, no transition), implement **only** the `EventDuelCalculateManeuverValues` branch and skip `EventResolveManeuver` entirely. The framework still rolls back the calc on cancel, and there's nothing to resolve. Reference: `Maneuver_03011` ("control X at duel location" → `+1 Riposte`).

### "You control a trait X at the duel location" gate

```php
$actor = $theah->getDuelRoundActor();
if ($actor === null) return false;

foreach ($theah->getCharactersAtLocation($actor->Location) as $character)
{
    if ($character->ControllerId != $playerId) continue;
    if ($character->hasTrait("Thug") || $character->hasTrait("Bodyguard")) return true;
}
return false;
```

`$actor->Location` is the canonical "this location" in a maneuver — duels always take place at the actor's (and adversary's) location, and there is no separate "duel location" global. The `ControllerId == $playerId` check excludes uncontrolled characters (their `ControllerId == 0`), so no extra `isControlled()` call is needed. Reference: `Maneuver_03011`.

### "-X [Stat] • Wound the adversary" pattern

A common maneuver shape. Two-phase wiring:

```php
if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
{
    $owner = $this->getOwningCard($event->theah);
    $event->thrust -= 1;   // or riposte / parry
    $event->explanations[] = sprintf($event->theah->game->translate("%s subtracts 1 Thrust."), $owner->getInjectCode());
}

if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
{
    $owner = $this->getOwningCard($event->theah);
    $adversary = $event->theah->getDuelRoundOpponent();

    $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
    $event->theah->eventCheck($woundEvent);
    $event->theah->queueEvent($woundEvent);
}
```

Note the `eventCheck($woundEvent)` call before `queueEvent` — gives prevention/redirection effects a chance to fire. Reference: `Maneuver_03009`, `Maneuver_01055` (Ranged variant), `Technique_01050` (Technique variant of the same shape).

### Cross-player maneuver sub-state (adversary picks something)

When the maneuver effect requires the **opposing** controller to pick (e.g., "they discard a card from their hand"), queue a `createTransitionEvent($adversary->ControllerId, ...)` from `EventResolveManeuver`, register the new state in `states.inc.php` under the Duel resolve-maneuver transitions, and implement `actFromManeuverWithId` to validate the pick. Reference: `Maneuver_01115` (Taunt — Finesse-gated adversary-discards-a-card flow).

## Pattern D — Reaction (`RiskReaction`)

Risk reactions are played from hand (the Risk card is the cost). Pre-commit hook enforces hand-only guard.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
// ...

class Reaction_NNNNN extends RiskReaction
{
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCard($event->theah);
        if ($owner === null) return;
        if (! ($owner->Location == Game::LOCATION_HAND)) return;   // required by pre-commit hook

        if ($event instanceof ...) {
            // ... queue reaction transition
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        // ... apply effect
        $this->setUsed($game->theah, true);
        $game->gamestate->nextState("done");
    }
}
```

Pre-commit hook requirements on RiskReaction:
- Literal `Location == Game::LOCATION_HAND` somewhere in the file (substring `grep` — `!=` does **not** satisfy it; structure your in-hand guard with the `==` form, e.g. `if (! ($owner->Location == Game::LOCATION_HAND)) return;`).
- Literal `$this->setUsed(` and `$this->isAvailable(` somewhere in the file.

References: `Reaction_01080` (Iron Reply-style — adds Parry during opposing maneuver), `Reaction_01140`, `Reaction_01088`, `Reaction_02048` (Pressure-to-cancel — multi-event family, saved-event re-emit on decline), `Reaction_03010` (cross-player choice flow after pay — see Pattern D.1).

### Pattern D.1 — Multi-stage cross-player RiskReaction with pay

When the Risk Reaction's effect itself involves another player choosing something (e.g., "Wound them unless their controller does X"), the standard RiskReaction shape (pay → `EventRiskReactionTriggered` → resolve inline) isn't enough. The pattern that works in-codebase, modeled after `Reaction_03010` (Manipulative):

1. **Internal `$stage` field** on the reaction (`''` / `'choice'` / `'pickN'` …) plus `$targetId` / `$opposingPlayerId` captured at trigger time. `getReactionButtonProperties()` and `getReactionDescription()` switch on `$stage`.
2. **`handleEvent` on the trigger event** → save target + opposing player ids, set `$stage = ''`, queue `createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id)` (offer to owner).
3. **`performReaction` with `$stage === ''`:**
   - `'use'` → queue `createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id)` + `createReactionPayTransitionEvent(...)`. The framework discards the Risk and fires `EventRiskReactionTriggered` after the pay.
   - `'pass'` → reset saved state (don't `setUsed` — the Risk stays in hand for future triggers).
4. **`handleEvent` on `EventRiskReactionTriggered && $event->internalId == $this->Id`** → set `$stage = 'choice'` and queue `createReactionTransitionEvent($this->opposingPlayerId, $owner->Id, $this->Id)`. The opposing player becomes the active player in `playerReaction`; the reaction's `getReactionButtonProperties()` (driven by `$stage='choice'`) renders the opponent's options. **If the choice is moot (e.g., opponent can't legally pick "return"), apply the wound/effect immediately + `finalize()` instead of transitioning.**
5. **`performReaction` with `$stage === 'choice'`** → branch on `$reactionId` → either apply the wound immediately + `finalize()`, or advance to `$stage = 'pickN'` and queue another reaction transition to the same opposing player.
6. **`finalize()`** → call `$this->setUsed($theah, true)` + reset stage / saved ids. The Risk is already in discard from the pay step.

Key gotchas:
- After the pay step, `$owner->Location` is `LOCATION_<DISCARD>`, **not** `LOCATION_HAND`. The in-hand guard in your `EventApproachCharacterPlayed` / trigger branch correctly skips re-triggering on subsequent triggers; the `EventRiskReactionTriggered` branch doesn't need it (and shouldn't have it).
- `createReactionTransitionEvent($opponentId, …)` still works after the Risk is in discard — the reaction object lives on `$theah->cards` (the discarded Risk is still in the cards map) and the `playerReaction` state machinery is owner-card-agnostic.
- `isAvailable()` returns `!Used`. Don't `setUsed(true)` until `finalize()`, or the mid-flow `playerReaction` state won't be able to render its `$stage`-dependent buttons cleanly.
- Cross-stage notifications: emit the "you used the Reaction" message from your `EventRiskReactionTriggered` handler (after pay) rather than from the offer-stage `performReaction`, so the announce-order matches the actual cost being paid.

### Trigger event distinction: `EventApproachCharacterPlayed` vs `EventCharacterMustered`

These do **not** overlap:
- `EventApproachCharacterPlayed` fires only during the Approach Phase (`StatesTrait::stApproachPhase`). It is the canonical "approach character played from the approach deck to home."
- `EventCharacterMustered` fires only from card effects that muster a character (Chance Meeting `_03cd03`, Réputation Méritée `_01072`, Bravos `_01024`, Don Constanzo's Reaction `_03003`, etc.). It is **not** fired during the Approach Phase.

If your trigger phrasing is "after a character is mustered from an Approach deck," you need **both events**. For `EventCharacterMustered`, filter on `$event->fromLocation == Game::LOCATION_APPROACH` (the field is populated by `EventHub`'s central handler before the move, so by the time card handlers see the event the field reflects the pre-move source). Don't try to read `$character->Location` for the source — `runEventHubAfterCards = false` means the hub has already moved the character to the destination by the time your handler runs.

### `EventCharacterPutIntoApproachDeck` semantics

This is the framework op for sending a character back to their owner's Approach deck (canonical use: `Reaction_01202` Object of Wonder; also `Reaction_03010` Manipulative). The hub handler:
- Moves the card to `LOCATION_APPROACH`.
- **Resets in-play state**: `Wounds`, `WoundsHealedIncoming`, `IsDying`, `Engaged` are all zeroed. A card in the Approach deck has no memory of its prior life.
- **Sends `cardRemovedFromPlay`** when the character was in play (city or `LOCATION_PLAYER_HOME`) at the moment of put-back, so other players' clients animate the leave.
- Sends a private `approachCardsReceived` to the owning player so their approach-deck UI syncs.

You generally don't need to queue a separate `createCardRemovedFromPlayEvent` or manually zero state — the hub does it. Just queue `createCharacterPutIntoApproachDeckEvent($ownerId, $characterId)` and any follow-on events (e.g., `createCharacterMusteredEvent` for a swap).

## Pattern E — Passive on the Risk class

For "While [condition] …" or other always-on effects of the Risk itself (typically combat-card cost discounts or in-duel stat modifiers), override `eventCheck` / `handleEvent` directly on the Risk class. Don't create an Action/Reaction file for passives.

```php
class _NNNNN extends Risk
{
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->cardId == $this->Id)
        {
            // ... modify the event
        }
    }
}
```

References: `Maneuver_01084::getManeuverFromCombatCardDiscount` (-1 cost when adversary engaged — note this is on the *Maneuver*, not the Risk class, because the discount applies only when this card is being played as a maneuver).

## State Wiring (`states.inc.php`)

For Pattern A City Actions, add a transition entry under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. Most Risk City Actions that issue challenges use the shared chooser:

```php
"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
```

If your Action transitions to a custom sub-state for a non-challenge effect, add a `States::HIGH_DRAMA_PLAYER_TURN_NNNNN` constant in `States.php` plus a state definition in `states.7s5s.php` (or a GameState class in `States/<expansion>/`). State ID convention: `4` + `CardNumber` zero-padded (e.g., `_03008` → `403008`). Don't engineer around hypothetical CD-card collisions. (Memory feedback.)

For Pattern C Maneuvers that transition to a sub-state (e.g., `Maneuver_01115`), add an entry under the duel's resolve-maneuver transition map and define the state. Mirror `Maneuver_01115`'s wiring.

### GameState class vs legacy array state

Two formats coexist for sub-state definitions:

- **Legacy array** in `states.7s5s.php` (e.g., `States::HIGH_DRAMA_PLAYER_TURN_01059`). Supports `""` as the default unnamed transition; the Action calls `$game->gamestate->nextState()` (no arg).
- **GameState class** in `modules/php/States/<expansion>/State_highDramaPhase<NNNNN>.php` (e.g., `State_highDramaPhase03009`, `State_highDramaPhase03cd01_2`). Uses **named transitions** in the `transitions:` array (e.g., `"locationChosen" => HIGH_DRAMA_PLAYER_TURN_EVENTS`); the Action must call `$game->gamestate->nextState("locationChosen")` to match the named key. Don't use `""` as a transition key on GameState classes.

For new card work, prefer the GameState class format. Model after `State_highDramaPhase03009` for a single-step location-chooser, or `State_highDramaPhase03cd01_2` for one with an `actBack` to a previous sub-state.

```php
class State_highDramaPhase<NNNNN> extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_<NNNNN>,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase<NNNNN>",
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('<Card Name>') . clienttranslate(': ${you} must choose ...:'),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void { $this->game->actFromCardWithLocations($locations); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState("zombie"); }
}
```

`actFromCardWithLocations` (string-encoded JSON array of location names) dispatches into the framework, which calls your Action's `actFromActionWithIds(Game $game, int $state, string $stateName, array $ids)` with `$ids[0]` being the chosen location string.

## JS State Hooks

When you add a card-specific sub-state, you usually need three matching JS handlers. For the **`modules/js/On*.faf.js`** files (mirrored for `_7s5s` and `tac`):

- **`OnEnteringState.faf.js`** — under the `methods` map, add `'highDramaPhase<NNNNN>': () => { ... }`. Highlight the performer, make valid targets selectable, stash chosen ids into `this.clientStateArgs` for cleanup.
- **`OnUpdateActionButtons.faf.js`** — add a confirm button. For location chooser: `this.addActionButton('actCityLocationsSelected', _('Confirm Location'), () => this.onCityLocationsSelected());` + `dojo.addClass('actCityLocationsSelected', 'disabled');`. For card chooser: `actChooseCardSelected` + `onChooseInPlayCardConfirmed`.
- **`OnLeavingState.faf.js`** — undo highlights / `resetCityLocations()` / clear `this.clientStateArgs`.

Pattern reference for the trio: `highDramaPhase03cd01_2` (Penya — location chooser with both performer and target highlight) and `highDramaPhase03009` (single-performer + location-chooser).

## Pre-Commit Hook Compliance

The `.githooks/pre-commit` hook checks staged PHP files. Risk-related rules:

| Class shape | Required literal strings |
|---|---|
| `extends RiskCityAction` | `createActionResolvedEvent` somewhere in the file (the comment `// createActionResolvedEvent() is called when the challenge is resolved` satisfies the hook for challenge-issuing actions where resolution fires it elsewhere). |
| `extends RiskAction` | Same as RiskCityAction. |
| `extends Maneuver` | An `EventManeuverCanceled` handler OR the comment `// EventManeuverCanceled handler not needed`. |
| `extends RiskReaction` | `Location == Game::LOCATION_HAND` check, plus `$this->setUsed(` and `$this->isAvailable(` literal calls. |
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent()` AND `createSorcererAbilityPlayedEvent()` literal calls. |
| Mixing `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on the **same** class | **Forbidden** — split into separate ability classes if the card text demands both. |

A Risk card that both extends `Risk` AND has Actions/Maneuvers/Reactions in separate files means the hook runs per-file: the Risk class itself has no Action/Reaction lint, but each ability file is checked independently.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Risk class:   `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:       `...\cards\<expansion>\actions`
  - Reaction:     `...\cards\<expansion>\reactions`
  - Maneuver:     `...\cards\<expansion>\maneuvers`
- **State ID convention:** `4<NNNNN>` for High-Drama player-turn states owned by a card. (Memory feedback.)
- **"Opposing"** means BOTH different controller AND same location.
- **Modified stats** (`ModifiedInfluence`, `ModifiedFinesse`, …) — use these for live comparisons, not the printed base values.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.
- **Typed PHP parameters required.** Every function/method signature must declare a type for every parameter — no bare `$foo`. Use concrete types (`Card $owner`, `Character $performer`, `Game $game`, `Theah $theah`, `Event $event`, `int $cardId`, `string $reactionId`). Add the `use` import.
- **"Strega" / "Mercenary" / "Diplomat" / "Duelist" / etc.** are **mechanical performer-trait gates**, not flavor. Enforce via `hasTrait("Strega")` on the performer / `getDuelRoundActor()`. They are NOT Sorcerer abilities — do NOT `implement ISorcererAbility` for them. Only the literal "Sorcerer" keyword triggers `ISorcererAbility`. They can stack.
- **`IRiskThatTargetsCharacters`** — mark on the Risk class itself when any of its abilities targets a character (Actions/Reactions/Maneuvers that fire `EventCharacterTargeted` or use `IAbilityThatTargetsCharacters`). Applies equally for friendly-target choosers, not just enemy-target. Compare `_01083`, `_01115`, `_03008`, `_03011`.

## Cross-Cutting Helpers

- `$theah->getCharactersInCityByPlayerId(int $playerId): array` — characters of `playerId` currently at city locations.
- `$theah->getCharactersInPlayByPlayerId(int $playerId): array` — wider net: characters in city or home.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing characters at a location.
- `$theah->getCharactersAtLocation(string $location): array` — everyone at a location (defensive: filter by `isControlled()` and `ControllerId` when "opposing" is the intent).
- `$theah->cardInCity(Card $card): bool` — true when the card is at a city location.
- `$theah->getDuelRoundActor(): ?Character` / `getDuelRoundOpponent(): ?Character` — the round's participant + adversary.
- `$theah->getDuelChallengerId() / getDuelDefenderId() / getDuelOpponentId(int $actorId)` — id-only accessors.
- `Game::IN_DUEL` global — true between duel start and end.
- `Game::DUEL_GAMBLED` global — true after the actor locks in a combat card via gamble; cleared at end of round.
- `Game::CHOSEN_PERFORMER` / `CHOSEN_TARGET` / `CHALLENGE_TYPE` / `CHALLENGE_STAT` globals — set in `handleEvent` on `EventActionTriggered` to brief the challenge sub-state machine.
- `$character->canChallenge(): bool` — true when the character can initiate a challenge (not engaged, in city, not Brute-locked, etc.).
- `$character->ModifiedInfluence` / `ModifiedFinesse` / `ModifiedCombat` / `ModifiedResolve` — live stats.
- `$this->getInjectCode(): string` — inline-styled card name for notifications.

Event factories you'll likely need:
- `createTransitionEvent($playerId, $sourceId, string $internalId, ?int $abilityId = null)` — move into a sub-state via the `*_EVENTS` transitions table.
- `createActionResolvedEvent($playerId, $actionId)` — fire when the Action's effect is fully resolved. NOT needed for challenge-issuing actions (the challenge resolution flow fires it).
- `createCardDrawnEvent($playerId, string $reason)` — draw one card.
- `createGainLethalEvent($actorId, Theah $theah)` — grant Lethal in a duel round.
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)` — move into the reaction's player-button state.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/Risk.php` | Base class — IFactionCard + IWealthCost + FactionCardTrait + WealthCostTrait. |
| `modules/php/cards/actions/RiskAction.php` | Base for Risk Actions. Defaults to in-hand requirement; `getPerformersForAction` adds in-play characters. |
| `modules/php/cards/actions/RiskCityAction.php` | Base for Risk City Actions. Requires a friendly character in the city; filters performers to city characters. |
| `modules/php/cards/maneuvers/Maneuver.php` | Maneuver base class. Default `ResetOnDuelEnd = true`. Hooks: `EventManeuverActivated` (sets Used), `EventDuskEndOfDay`, `EventDuelEnd`. |
| `modules/php/cards/reactions/RiskReaction.php` | RiskReaction base. Adds the "Played \<card\>" announcement suffix. |
| `modules/php/cards/_7s5s/_01083.php` (Legendary Reputation) | **RiskCityAction issuing Combat challenge with Leader-only intervention.** Custom challenge type. Uses shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`. |
| `modules/php/cards/_7s5s/_01084.php` (Master of Valroux Style) | **Duelist Maneuver** with combat-card discount on engaged adversary, +1 Riposte, draw card, +1 Thrust to adversary's combat card next round. `getManeuverFromCombatCardDiscount` pattern. Includes `IncreaseAdversaryThrust` state field with explicit `EventManeuverCanceled` reset. |
| `modules/php/cards/_7s5s/_01115.php` (Taunt) | **Risk with City Action and Maneuver, IRiskThatTargetsCharacters.** Maneuver gates on Finesse comparison and transitions to adversary-controller hand-pick sub-state. |
| `modules/php/cards/_7s5s/_01061.php` (Well-Equipped) | **Risk with Action and Maneuver.** Maneuver conditionally draws based on equipped Weapon attachments. |
| `modules/php/cards/faf/_03008.php` (Arrogant) | **Risk with Influence-gated City Action Combat challenge AND a Gambling Maneuver.** `NORMAL_CHALLENGE_TYPE` (no custom intervention rules); Influence comparison in `isValidTargetForAbility`. Gambling Maneuver gated on `DUEL_GAMBLED` plus actor>adversary Influence. |
| `modules/php/cards/faf/_03009.php` (Follow the Thread) | **Sorcerer Strega Action (not City Action) that moves the performer to an adjacent location filtered by destination contents (enemy character OR available Mercenary) + Strega Maneuver (-1 Thrust, wound adversary).** Uses `parent::getPerformersForAction` so home performers are eligible. Pairs with `State_highDramaPhase03009` (GameState class with `"locationChosen"` named transition). |
| `modules/php/cards/faf/_03011.php` (Provoking the Pack) | **Friendly-target City Action move + "control trait at duel location" Gambling Maneuver.** City Action gated on "performer is opposed" (`getOpposingCharactersAtLocation > 0`); target is one of the player's own characters with `Thug`/`Bodyguard` at an adjacent location (incl. home). The friendly-target counterpart to Taunt's enemy-target chooser — same `IAbilityThatTargetsCharacters` shape with the controller check flipped. Maneuver is pure `+1 Riposte` in calc with no `EventResolveManeuver` handler. Pairs with `State_highDramaPhase03011` (GameState class with `"targetChosen"` named transition, `actFromCardWithId` possible action). |
| `modules/php/cards/_7s5s/_01059.php` (Regroup) | **Simple "Move your performer to an adjacent City location" City Action.** The canonical move-to-adjacent template — uses legacy array-format state `highDramaPhase01059` (`""` default transition). |
| `modules/php/cards/faf/_03010.php` (Manipulative) | **RiskReaction with multi-stage cross-player choice on top of the pay state (Pattern D.1).** Triggers on both `EventApproachCharacterPlayed` AND `EventCharacterMustered` (filtered by `$event->fromLocation == LOCATION_APPROACH`). After pay → `EventRiskReactionTriggered` chains a second `createReactionTransitionEvent` to the opposing player for the return-vs-wound choice. `$stage` field drives `getReactionButtonProperties()`. Reset of in-play state on return-to-Approach is handled centrally by the EventHub `EventCharacterPutIntoApproachDeck` handler. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (City Action / Action / Maneuver / Reaction / Passive). Riposte/Parry/Thrust numbers go on the constructor and are not a "pattern."
2. Confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's NNNNN, `WealthCost` is set, combat stats match the printed card (set `DashedX = true` for printed-dashed stats), all Traits exist in `TraitNames::$TraitsJson`.
3. Mark `implements IRiskThatTargetsCharacters` on the Risk class when any of its abilities targets a character. The interface marker lives on the Risk class itself, not the Action/Reaction/Maneuver.
4. Each Action/Maneuver/Reaction is its own file in the corresponding subdirectory (`actions/`, `maneuvers/`, `reactions/`). Create the subdirectory if the expansion doesn't have one yet.
5. For City Action challenges that route through the shared challenge target chooser, add `"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` to `states.inc.php`. For non-challenge sub-states, add a `States::HIGH_DRAMA_PLAYER_TURN_<NNNNN>` constant (`4<NNNNN>`) plus a state definition (or GameState class).
6. **Parse keyword(s) literally** before picking interfaces:
   - "Sorcerer …" → `implements ISorcererAbility` + emit start/played events.
   - "Strega …" / "Mercenary …" / "Diplomat …" / "Duelist …" / "Gambling …" → performer-trait or duel-state gate. NOT a Sorcerer ability.
   - Both can stack.
   - **"City Action:" vs "Action:"** — only the "City" prefix restricts performers to city characters. A plain "Action:" admits home performers too, even when the effect *implies* city movement. Don't pre-filter to `getCharactersInCityByPlayerId(...)`; start from `parent::getPerformersForAction(...)`.
7. **Use Modified stats** (`ModifiedInfluence`, `ModifiedFinesse`, …) for in-duel and in-city comparisons.
8. **Typed parameters** on every function/method signature. No bare `$foo`. Add `use ...\cards\Card;` (etc.) imports as needed.
9. Pre-commit hook checks on every file:
   - **RiskCityAction / RiskAction subclass:** `createActionResolvedEvent` literal string present (real call or comment).
   - **Maneuver subclass:** `EventManeuverCanceled` handler OR the literal comment `// EventManeuverCanceled handler not needed`.
   - **RiskReaction subclass:** `Location == Game::LOCATION_HAND` guard AND `$this->setUsed(` AND `$this->isAvailable(` literal strings. The hook's `grep` is exact-substring on `==` — `Location != Game::LOCATION_HAND` does NOT satisfy it. Use the negated `==` form: `if (! ($owner->Location == Game::LOCATION_HAND)) return;`.
   - **`implements ISorcererAbility`:** both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` called.
   - No class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards`.
10. Lint touched PHP files (`php -l`) before committing.
11. **For card-specific sub-states (GameState class):** the Action's `nextState(...)` argument must match a *named* transition key (e.g., `"locationChosen"`); the empty `""` default works only for legacy array-format states in `states.7s5s.php`.
12. **For card-specific sub-states:** also add `OnEnteringState`/`OnUpdateActionButtons`/`OnLeavingState` handlers in the matching expansion's JS file (e.g., `modules/js/On*.faf.js`).
