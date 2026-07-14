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
- `modules/php/cards/faf/_03012.php` (Subtle) — **Sorcerer Strega RiskReaction that mutates `Game::CHALLENGE_STAT` mid-challenge.** Triggers on `EventCharacterIntervened`; gates on intervener's `Strega` trait. After pay, `EventRiskReactionTriggered` sets `CHALLENGE_STAT = STAT_INFLUENCE` so downstream threat calc uses the new stat. Single-stage (no cross-player choice). Exemplar for "the challenge becomes a [Stat] challenge" Reactions and for Reactions that mutate the live challenge globals rather than directly wounding/moving/drawing.
- `modules/php/cards/faf/_03020.php` (Commanding) — **"Leader Action" target-and-move-Home + "Leader Reaction" that cancels a Renown movement keyed off `EventRenownMovingBetweenLocations`.** The Reaction `implements ICancelReaction` (required — without it, the post-pay `EventRiskReactionTriggered` is `queueEvent`'d at `MEDIUM_PRIORITY` and loses the race to the still-pending `HIGH_PRIORITY` Add/Remove events). `stackEvent` is used at every step (reaction transition, pay events) so the Reaction interleaves ahead of the pending batch. The Action uses `getLeaderByPlayerId` directly (no `RequiresPerformerSelected` — Leader is uniquely determined). Exemplar for Pattern D.3 (cancel pending high-priority batch events).
- `modules/php/cards/faf/_03021.php` (Cornered) — **RiskCityAction issuing a Combat challenge to a trait-filtered (Sorcerer OR Monster) opposing target, with the performer engaged as a cost and side effects on refuse/intervene.** Mints `CORNERED_CHALLENGE_TYPE` purely as a correlator so the Risk's `handleEvent` can identify its own challenge when reading `EventChallengeRejected` (engage the refuser) and `EventCharacterIntervened` (wound the intervener) — gates themselves stay normal. Exemplar for "side-effect-on-refuse/intervene → mint a CHALLENGE_TYPE" and for filtering engaged performers out (`! $p->Engaged` layered on `canChallenge()`) when the text imposes an engage cost.
- `modules/php/cards/_7s5s/_01082.php` (A Heroic End) — **Pure-data Final Strike Maneuver** (+2 Threat + Lethal when participant dies). Track participant on `EventResolveManeuver`, react on `EventCharacterDestroyed` while `IN_DUEL`. No state transition — pure data mutation only. The baseline Final Strike shape; reach for `_03022` when the on-death effect requires a player choice.
- `modules/php/cards/faf/_03022.php` (Overzealous) — **Final Strike Maneuver with a post-death player choice (en garde target) + conditional draw.** Same participant-tracking shape as `_01082`, but the on-death effect queues a `createTransitionEvent` into an end-of-round sub-state where the dead participant's controller picks an En Garde target. Exemplar for Pattern C.1 (post-death player choice), the `DuelLocation` capture (actor is in the locker by selection time), the `DUEL_END_OF_ROUND_NNNNN` state-family naming, and the pass-button + gate-on-pass discipline for "target if able" prompts.
- `modules/php/cards/faf/_03023.php` (Second Wind) — **Gambling Maneuver that suppresses end-of-round threat→wound conversion and carries the threat to next round.** Intercepts `EventCharacterBeingWounded` by signature (`characterId == actor.Id && sourceId == adversary.Id` — that pairing only happens for the threat→wound conversion); zeroes `$event->wounds`, captures the original amount, adds it to `PENDING_CHALLENGER_THREAT` / `PENDING_DEFENDER_THREAT` (which `stDuelNewRound` reads onto the next round's starting pool), and zeroes `duel_round.wounds_taken` for the same row so the UI display and `duelParticipantWoundsTaken()` cross-round aggregate stay consistent. Exemplar for Pattern C.2 (suppress end-of-round conversion ± carry-forward) and a worked example of `Maneuver_02039`'s `PENDING_*_THREAT` mechanism as the supported cross-round threat channel.
- `modules/php/cards/_7s5s/_01135.php` + `Maneuver_01135` — **Choice-at-activation Maneuver: same player picks which branch the calc applies.** "+2 Parry, or wound adversary and -2 Thrust to their next round." `EventManeuverActivated` `stackEvent`s a `createTransitionEvent` into `DUEL_RESOLVE_MANEUVER_01135`; player picks via `actFromCardWithId` ({id:1}/{id:2}); the choice sets a private boolean on the Maneuver; calc branches on the boolean. The baseline template for Pattern C.3.
- `modules/php/cards/faf/_03024.php` + `Maneuver_03024` (Superstitious) — **Pure-calc Pattern C.3 variant with an adversary-trait gate.** "Maneuver: If the adversary is a Sorcerer or Monster • +2 Parry or +2 Thrust." `isAvailableToPlayer` reads `getDuelRoundOpponent()->hasTrait('Sorcerer'|'Monster')`. No `EventResolveManeuver` handler — both branches are pure calc, so the calc-event branch on the stored choice is the entire effect.
- `modules/php/cards/faf/_03031.php` (Altruistic) — **RiskReaction that intercepts wound/move/engage effect events and redirects them to another of your characters at the same location (Pattern D.4).** Adapted from `Reaction_02016` (Cross of the Martyrs) clone-cancel-reemit, but effect-based (not "targets") and with Risk pay deferred to `EventRiskReactionTriggered`. "Performer at that location" = `getCharactersAtLocationByPlayerId` excluding the character currently being affected. `isValidTargetForAbility` only when the source ability implements `IAbilityThatTargetsCharacters` ("if they are able"); otherwise `releaseEvent` directly.
- `modules/php/cards/faf/_03032.php` (Bloody Entrance) — **Sorcerer City Action: wound your performer, move them to any location, then grant a mandatory follow-up action locked to that same performer (Pattern A.2).** Uses `EXTRA_ACTIONS` + new `EXTRA_ACTION_PERFORMER` framework globals. Destination pool matches `Action_03029`'s "any location" helper (all city locations + Home if not already there). Pairs with `State_highDramaPhase03032` (GameState location chooser, same JS shape as `03009`).
- `modules/php/cards/faf/_03033.php` (Glorious) — **Forced on the Risk class (Pattern E.1) + pure-resolve Gambling Maneuver.** Forced: after your adversary is destroyed while this card is in your dueling line, heal your participant. Maneuver: `DUEL_GAMBLED` + `ModifiedInfluence >=` adversary → wound adversary (no calc branch). Exemplar for "adversary destroyed / dueling line" Forced, equal-or-greater Influence gates (`>=` vs `_03008`'s strict `>` for "more than"), and wound-only Gambling Maneuvers.
- `modules/php/cards/faf/_03034.php` (La Voix des Sans Voix) — **Diplomat City Action: engage performer • En garde another character you control at this location • then that character may heal a wound / if they do not, draw a card (Pattern A.3).** Trait-gated Diplomat + `!Engaged` engage cost; friendly same-location Engaged targets; engage resolves on `EventActionTriggered` before the chooser; second state is `{id:1}` heal / `{id:2}` draw (auto-draw when `Wounds == 0`). Exemplar for Diplomat City Actions, same-location friendly En Garde, and "may X / if they do not, Y" same-player branch choice.
- `modules/php/cards/faf/_03035.php` (Loyal) — **Pressure +1 RiskReaction (Pattern D.2.1) + multi-step C.3 Maneuver (wound other character • +1 Riposte or +2 Thrust).** Reaction triggers on `EventPressureOccuring`, gates on more non-Mercenaries than each opponent at that location, mints `LOYAL_PRESSURE_TYPE` + `LOYAL_PLAYER_ID` after pay; `pressureLocation()` adds +1. Maneuver: character chooser then Riposte/Thrust buttons; **every** intermediate transition must `stackEvent` or calc races ahead of the choice. Exemplar for pressure-total Reactions and multi-step choice-at-activation Maneuvers.
- `modules/php/cards/faf/_03036.php` (Valroux Exemplar) — **Finesse-gated combat-card cost discount + Duelist Maneuver that scales Riposte off the dueling line and conditionally forces an adversary hand discard (Pattern C.4).** Discount via `getManeuverFromCombatCardDiscount` when `ModifiedFinesse >` adversary. Maneuver: `+1 Riposte` per other dueling-line card (`01166` count); if ≥3 other cards, adversary discards (skip transition when hand empty). Exemplar for composing discount + line-count calc + conditional cross-player discard on one Maneuver.
- `modules/php/cards/faf/_03045.php` (Curious) — **Plain Action (Pattern B.1 claim-control destination) + Gambling Maneuver that wounds your participant and adds Riposte.** Action: wound performer • move to adjacent location **controlled by an opponent** (`getControllerForLocation` — claim control, not "enemy present"). Maneuver: `DUEL_GAMBLED` only (no Influence gate) • wound `getDuelRoundActor()` • `+2 Riposte` in calc. Exemplar for claim-control location filters vs `_03009`'s content filters, and for wound-participant (self) vs wound-adversary Gambling Maneuvers.
- `modules/php/cards/faf/_03046.php` (Passionate) — **Two Pattern D.2 RiskReactions on `EventCharacterIntervened` that En Garde a fixed trigger-named character after pay.** Duelist (`Reaction_03046a`): you intervene → engarde the intervener. Pirate (`Reaction_03046b`): your Pirate's challenge, adversary intervened → engarde the challenger. Exemplar for dual a/b Reactions on one Risk, for "challenge accepted if adversary intervened" mapping to `EventCharacterIntervened` (**not** `EventChallengeAccepted`), and for engarde deferred to `EventRiskReactionTriggered`.
- `modules/php/cards/faf/_03047.php` (Proper Drama) — **Scoundrel + Duelist Maneuvers (Pattern C.5).** Scoundrel (`Maneuver_03047a`): `+1 Riposte` + if adversary gambles next round, **you** choose their combat card (hijack on `EventDuelGambleCardsRevealed`, public choose state, `actGambleCardChosen` uses actor deck). Duelist (`Maneuver_03047b`): adversary cannot gamble next round (`eventCheck` on `EventDuelAttemptGamble`, mirror `Technique_02037`). Exemplar for dual a/b trait Maneuvers, next-round gamble locks, stolen gamble chooser, and `getArgsFromManeuver` + `argsForState` (not an ArgumentsTrait helper).

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
- **`IRiskThatTargetsCharacters`:** mark on the Risk class itself (not its Actions/Maneuvers) when any of its abilities targets a character — **including when the target is your own character** (the interface tracks "this Risk hands the player a character chooser", not enemy-only targeting). Compare `_01083`, `_01115`, `_03008`, `_03011`, `_03034`. **Do not** mark it for location-chooser Actions (`_03009`, `_03032`, `_03045`) — those pick a destination string via `actFromCardWithLocations`, not a character. Performer selection via `RequiresPerformerSelected` alone is also not a "targets characters" chooser. Fixed-target Reactions that engarde/wound a character named by the trigger (`_03046`, `_03012`) also skip it — there is no chooser.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code.

| Card phrase | Pattern |
|---|---|
| **`<b>City Action:</b>`** | Pattern A — `RiskCityAction`. The Action lives in `cards/<expansion>/actions/Action_NNNNN.php`. Performer must be in the city (framework helper). |
| **`<b>Action:</b>`** (no "City") | Pattern B — `RiskAction`. Defaults to requiring the Risk in hand (`Card::Location == LOCATION_HAND`); override `overrideInHandCheck` only when the card text implies otherwise. **Performer pool is home + city** — do NOT filter to city characters even when the effect implies city (e.g. "move to an adjacent location"). The keyword "City" in the heading is mechanical: if absent, home performers are eligible too. |
| **`<b>Maneuver:</b>`** / **`<b>Duelist Maneuver:</b>`** / **`<b>Scoundrel Maneuver:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern C — `Maneuver` subclass in `cards/<expansion>/maneuvers/Maneuver_NNNNN.php`. Trait-prefixed Maneuvers add an `isAvailable` gate (`hasTrait` or `DUEL_GAMBLED`). |
| **`<b>Reaction:</b>`** | Pattern D — `RiskReaction`. Pre-commit hook requires hand-only guard (`Location == Game::LOCATION_HAND`) + `setUsed`/`isAvailable` literal calls. |
| **"When an opponent's ability would wound/move/engage your character"** (no "target" wording) | Pattern D.4 — effect-event redirect `RiskReaction`. Intercept `EventCharacterBeingWounded` / `EventCardMoving` / `EventCardEngaged` (± `EventCharacterIntervened` for duel intervention). Gate on opponent source, not `IAbilityThatTargetsCharacters`. See `Reaction_03031`. |
| **"While [adversary/condition] …"** / **"If your participant has more [Stat] … this card has -1 cost"** (combat-card cost or stat modifier) | Pattern E — cost discounts via `getManeuverFromCombatCardDiscount` on the Maneuver (`_01084`, `_03036`); other always-on effects may override `handleEvent` on the Risk class. |
| **`<b>Forced:</b>`** (no player choice; fires automatically) | Pattern E.1 — `handleEvent` on the **Risk class itself**, not a separate Action/Reaction/Maneuver file. Common gates: `Location == LOCATION_DUELING_LINE`, `IN_DUEL`, destroyed character is your adversary. See `_03033` (Glorious), `_01102` (Unfortunate). |
| **`<b>Sorcerer …:</b>`** | The ability class (Action/Reaction/Maneuver) additionally `implements ISorcererAbility` — must emit `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). |
| **`<b>Strega …:</b>`** / **`<b>Mercenary …:</b>`** / **`<b>Diplomat …:</b>`** / **`<b>Duelist …:</b>`** | **Mechanical performer-trait gates**, NOT Sorcerer abilities. Enforce via `hasTrait("Diplomat")` (etc.) on the chosen performer or `getDuelRoundActor()`. Do NOT `implement ISorcererAbility` for these. Can stack with Sorcerer ("Sorcerer Strega Reaction" is both). |
| **`<b>Leader …:</b>`** | **Leader is the performer**, by mechanical restriction. Each player has at most one Leader (`getLeaderByPlayerId($playerId)`) — fetch it directly. Do **not** set `RequiresPerformerSelected = true`; there's no choice to make. For "Leader Action", `isValidTargetForAbility` resolves the Leader via the Risk's `ControllerId` instead of reading `CHOSEN_PERFORMER`. Mirror `Action_01024` (Bravos), `Action_03020` (Commanding). "Leader Reaction:" follows the same shape — the gate is "player owns a Leader at the printed location reference." |
| **"… then they may perform another action"** with *(It must be performed and they must be the performer of the action)* | Pattern A.2 — grant `EXTRA_ACTIONS = 1` **and** set `EXTRA_ACTION_PERFORMER` to the wound/move performer's id. Framework enforces same character + no Pass. See `_03032`. Do **not** rely on `EXTRA_ACTIONS` alone — it only keeps the same *player* on turn. |
| **"Engage your performer • En garde another character you control … Then, that character may heal … If they do not, draw"** | Pattern A.3 — Diplomat (or other trait) City Action: engage cost + same-location friendly Engaged target + may-heal / else-draw branch. See `_03034`. |
| **"When a pressure occurs … Add +1 to your total for the pressure"** | Pattern D.2.1 — RiskReaction on `EventPressureOccuring`; mint a new `PRESSURE_TYPE` binary flag + player-id global; apply in `pressureLocation()`. Do **not** reuse `PRESSURE_BONUS` (Pack Tactics / Influence-only). See `_03035`. |
| **"Wound your other character at this location • +X [stat A] or +Y [stat B]"** | Pattern C.3 multi-step — character chooser state, then choice buttons; `stackEvent` **every** step until the calc-driving choice is recorded. See `_03035`. |
| **"+X [stat] for each other card in your dueling line"** (± **"If you have N or more other cards … adversary discards"**) | Pattern C.4 — count other cards at `LOCATION_DUELING_LINE` for the controller in calc; optional resolve-time discard transition when count ≥ N. See `_03036`, `Maneuver_01166`. |
| **"If your participant has more [Stat] than the adversary, this card has -1 cost"** | Pattern E via Maneuver — `getManeuverFromCombatCardDiscount` with Modified-stat comparison (not a separate Passive file). See `_03036`, `_01084`. |
| **"Move … to an adjacent location controlled by an opponent"** / **"claimed by an opponent"** | Pattern B.1 claim-control filter — `getControllerForLocation($location) != 0 && != performer->ControllerId`. Not the same as "enemy character at location" (`_03009`). See `_03045`. |
| **"Wound your participant • +X [stat]"** (Gambling / other Maneuver) | Pattern C — calc branch for the stat; resolve wounds `getDuelRoundActor()` (your participant), not the adversary. See `Maneuver_03045`, `Maneuver_02018`. |
| **"After your performer intervenes • En garde them"** / **"… challenge is accepted, if their adversary intervened • En garde your performer"** | Pattern D.2 on `EventCharacterIntervened` — engarde deferred to `EventRiskReactionTriggered`. Trait gate on the trigger-named performer. See `_03046`. |
| **Two distinct trait-prefixed Reactions on one Risk** | Split into `Reaction_NNNNNa` / `Reaction_NNNNNb` (mirror `_03027` / `_03016`). Do not merge into one class with a mode field. |
| **"The adversary cannot gamble during their next round"** | Pattern C.5 — arm on resolve; `eventCheck` `EventDuelAttemptGamble` for blocked adversary character. Mirror `Technique_02037`; clear via owner **ControllerId** on Risk Maneuvers. See `Maneuver_03047b`. |
| **"If the adversary gambles during their next round, you choose their combat card"** (± **+X [stat]**) | Pattern C.5 — arm on resolve; hijack on `EventDuelGambleCardsRevealed` (not AttemptGamble); transition to Maneuver owner; public choose state; `actGambleCardChosen` uses actor deck. See `Maneuver_03047a`. |
| **Two distinct trait-prefixed Maneuvers on one Risk** | Split into `Maneuver_NNNNNa` / `Maneuver_NNNNNb` (mirror `_01108`). Same discipline as dual Reactions. |

A single Risk freely combines these. `_01115` has both a City Action and a Maneuver. `_03008` has both a City Action and a Gambling Maneuver. `_03033` has both a Forced (on the Risk class) and a Gambling Maneuver. `_03034` is a single Diplomat City Action (Pattern A.3). `_03035` has both a pressure Reaction and a multi-step C.3 Maneuver. `_03036` composes a Finesse cost discount with a Duelist C.4 Maneuver (line-count Riposte + conditional discard). `_03045` has a plain Action (claim-control move) and a Gambling Maneuver (wound participant + Riposte). `_03046` has two Pattern D.2 intervene/engarde Reactions (Duelist + Pirate). `_03047` has dual a/b Maneuvers (Scoundrel choose-gamble + Duelist cannot-gamble). `_01083` is a single City Action only.

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

References: `Action_01061` (Well-Equipped's en-garde-equipped-performer Action), `Action_03009` (Sorcerer Strega Action that moves the performer to an adjacent location filtered by contents), `Action_03045` (wound performer + move to adjacent claim-controlled-by-opponent location).

### Pattern B.1 — "Move your performer to an adjacent location where …"

A common Risk Action shape: the player picks an *adjacent location* meeting some predicate (enemy character at it, available Mercenary at it, claimed by an opponent, etc.). See `Action_03009` (content filters) and `Action_03045` (claim-control filter ± wound cost). Wire it as:

1. **Filter performers** by trait gates (`Sorcerer`/`Strega`/etc.) and by "has ≥1 valid destination" — `parent::getPerformersForAction(...)` first, then `array_filter`.
2. **`handleEvent` on `EventActionTriggered`:** queue a `createTransitionEvent(..., "NNNNN", $this->Id)` to a card-specific location-chooser sub-state.
3. **`getArgsFromAction`:** in the sub-state, expose `performerId` + `locationIds` (the valid adjacent destinations).
4. **`actFromActionWithIds($ids)`:** `$ids[0]` is the chosen location string (BGA dispatches `actFromCardWithLocations` → `actFromActionWithIds`). Validate it's in the valid list, then queue effects + `createActionResolvedEvent(...)`. If Sorcerer, bracket with `createSorcererAbilityStartEvent` / `createSorcererAbilityPlayedEvent`. When the text also says **"Wound your performer"** before the move, queue `createCharacterBeingWoundedEvent` **then** `createCardMovingEvent` on confirm (both with `eventCheck`) — mirror `Action_03032` / `Action_03045`. Do not wound in `EventActionTriggered` before the chooser; resolve both costs when the destination is locked in.
5. **`$theah->getAdjacentCityLocations($performer->Location, $includeHome = false)`** is the right helper; pass `$includeHome = false` unless the rules text explicitly admits Home as a destination.
6. **`getCharactersAtLocation($location, $includeUncontrolled = true)`** when "available Mercenary" or other uncontrolled-character predicates are part of the destination filter. The default `$includeUncontrolled = false` will silently drop the available mercenaries.
7. **"Available Mercenary"** = `! $character->isControlled() && $character->hasTrait("Mercenary")`.
8. **"Enemy character"** = controlled by an opposing player: `$character->isControlled() && $character->ControllerId != $performer->ControllerId`. Don't conflate with "opposing" (which also requires same location).
9. **"Location controlled by an opponent" / "claimed by an opponent"** = **claim control**, not character presence. Filter with:

```php
$controller = $theah->game->getControllerForLocation($location);
return $controller != 0 && $controller != $performer->ControllerId;
```

WHY exclude `0`: uncontrolled city locations are not controlled by anyone, so they fail "controlled by an opponent." WHY not reuse `_03009`'s enemy-character scan: a location can have enemy characters and still be uncontrolled (or controlled by you). Claim state lives in `getControllerForLocation` / `$theah->getCityLocation($name)->Controller` (same source — see Pavel `_01120`). Reference: `Action_03045`.

**Location chooser ≠ character chooser:** do not `implements IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters` for B.1 Actions. JS is the `highDramaPhase03009` / `03032` / `03045` trio (`makeCityLocationSelectable` + Confirm Location).

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

### "Duelist Maneuver" / "Scoundrel Maneuver" / "Gambling Maneuver" — trait-prefixed gates

These are **mechanical performer-trait gates**, not Sorcerer abilities. Add an `isAvailable` predicate:

```php
// Duelist / Scoundrel / Pirate / … Maneuver:
$actor = $theah->getDuelRoundActor();
if (! $actor || ! $actor->hasTrait('Duelist')) return false;

// Gambling Maneuver:
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

`Game::DUEL_GAMBLED` is set true in `FrameworkActionsTrait::actChooseGambleCard` when the gambled combat card is locked in, and cleared in `stDoneRound`. See `Technique_03002` (Aja) for the same gate on the Technique side.

### "If your participant has more / equal or greater <Stat> than the adversary" gate

Parse the printed comparison literally — the operator is part of the card text:

| Card phrase | Operator |
|---|---|
| "more … than" / "greater … than" | `>` |
| "equal or greater … than" / "equal or lower … than" (on the *target*) | `>=` / `<=` |

```php
$actor = $theah->getDuelRoundActor();
$adversary = $theah->getDuelRoundOpponent();
// "more Influence than" → strict >
return $actor->ModifiedInfluence > $adversary->ModifiedInfluence;
// "equal or greater Influence than" → >=
return $actor->ModifiedInfluence >= $adversary->ModifiedInfluence;
```

Use **modified** stats (`ModifiedInfluence`, `ModifiedFinesse`, etc.), not the printed base — the comparison must honor live modifiers. Reference: `Maneuver_01115` (Finesse comparison), `Maneuver_03008` ("more" → `>`), `Maneuver_03033` ("equal or greater" → `>=`), `Technique_01196` (equal-or-greater Combat+Influence).

When the resolve effect wounds the adversary, also gate availability on `! $theah->game->characterIsInDiscardOrLocker($adversary)` so the maneuver is not offered against an already-destroyed opponent.

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

References: `Maneuver_01061` (conditional draw on equipped Weapon), `Maneuver_01084` (Duelist gate + adversary Thrust bonus next round + combat-card discount when adversary engaged), `Maneuver_01115` (cross-player hand-pick discard via `createTransitionEvent` to the adversary's controller), `Maneuver_01166` / `Maneuver_03036` (+N for each other dueling-line card), `Maneuver_03008` (Gambling gate + Influence comparison + Riposte+draw), `Maneuver_03009` (Strega gate + `-1 Thrust` in calc + wound adversary in resolve), `Maneuver_03011` (Gambling gate + "control trait X at duel location" → pure `+1 Riposte` in calc), `Maneuver_03033` (Gambling gate + equal-or-greater Influence → pure-resolve wound adversary, no calc), `Maneuver_03045` (Gambling gate only + `+2 Riposte` in calc + wound **participant** in resolve).

### "Wound your participant" vs "Wound the adversary"

Parse the wound target literally — both appear on Gambling Maneuvers and they are not interchangeable:

| Card phrase | Wound target in `EventResolveManeuver` |
|---|---|
| "Wound the adversary" / "Wound them" (adversary context) | `$theah->getDuelRoundOpponent()` — also gate `isAvailable` on `! characterIsInDiscardOrLocker($adversary)` when wound is the (or a) payoff. See `Maneuver_03009`, `Maneuver_03033`. |
| "Wound your participant" | `$theah->getDuelRoundActor()` — your own duel actor. No discard/locker availability gate (the actor is present to play the Maneuver). See `Maneuver_03045`, `Maneuver_02018`. |

When the same Maneuver also adds Riposte/Parry/Thrust, keep the stat mutation in `EventDuelCalculateManeuverValues` and the wound in `EventResolveManeuver` (calc can re-fire; resolve is once). Gambling gate remains `DUEL_GAMBLED` only unless the text adds a further comparison (`_03008` / `_03033`).

### Pattern C.4 — "+X [stat] for each other card in your dueling line" (± conditional adversary discard)

"Other cards in your dueling line" means every card at `Game::LOCATION_DUELING_LINE` for the Risk's controller **except this combat card itself**. By calc/resolve time the card is already in the line, so you must exclude it:

```php
$owner = $this->getOwningCard($event->theah);
$cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
unset($cards[$owner->Id]);
$count = count($cards);
$event->riposte += $count;   // or parry / thrust per the printed text
```

Pure scaling (no side effect) needs only the `EventDuelCalculateManeuverValues` branch — see `Maneuver_01166` (+1 Parry per other card). Skip the explanation line when `$count == 0` to avoid "adds 0 …" noise.

**Conditional "If you have N or more other cards … the adversary discards a card":** keep the calc branch unconditional (0 other cards → +0 is fine). In `EventResolveManeuver`, gate the discard transition on `$count >= N`. Also skip when the adversary's hand is empty:

```php
$hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
if (count($hand) == 0) return;
```

**WHY empty-hand skip at resolve, not `isAvailableToPlayer`:** the discard is an *extra* clause on a maneuver the player still wants for the Riposte scaling. Putting the hand check on availability (as `Maneuver_01108a` does when discard *is* the whole effect) would hide the maneuver entirely when the adversary has no cards. Resolve-time skip avoids a stuck activeplayer chooser without suppressing the useful calc.

Adversary hand-pick discard does **not** need `IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters` — those mark character choosers. Wire the sub-state like `Maneuver_01115` (JS: `factionHand.setSelectionMode('single')`, Confirm via `onCardDiscarded()`, enable Confirm in `EventHandlers.js` on selection).

References: `Maneuver_01166` (pure line-count calc), `Maneuver_03036` (Duelist + Riposte scaling + ≥3 discard), `Maneuver_01115` / `Maneuver_01108a` (discard chooser / hand-gated availability when discard is the only effect).

### Pattern C.5 — Next-round gamble control ("cannot gamble" / "you choose their combat card")

Two related texts that arm a lock on `EventResolveManeuver` for **the adversary's next round**. Split distinct trait-prefixed Maneuvers into `a`/`b` files (mirror `_01108` / `_03046`).

#### Cannot gamble (`Maneuver_03047b`, `Technique_02037`)

```php
// Arm on resolve:
$this->CancelAdversaryGamble = true;
$this->BlockedAdversaryCharacterId = $adversary->Id;

// Block in eventCheck (not handleEvent):
if ($event instanceof EventDuelAttemptGamble
    && $this->CancelAdversaryGamble
    && $event->actorId == $this->BlockedAdversaryCharacterId)
{
    throw new UserException(...);
}
```

**Clear via ControllerId on Risk Maneuvers:** Techniques clear when `$owningCharacter->Id == $event->actorId` on `EventDuelNewRound`. A Maneuver lives on a Risk in the dueling line — there is no owning character. Clear when the new round's actor `ControllerId == $owner->ControllerId` (your next turn starts). Also clear on `EventManeuverCanceled` / `EventDuelEnd`.

#### You choose their combat card (`Maneuver_03047a`)

Do **not** hijack on `EventDuelAttemptGamble` — the adversary must still commit to gambling and reveal. Hijack on **`EventDuelGambleCardsRevealed`** when `$event->actorId == $this->BlockedAdversaryCharacterId`:

1. `notify->all` waiting log (`must choose the adversary's combat card from the revealed gamble cards`) — state description alone is not enough for watchers.
2. `queueEvent(createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id))`.
3. Wire `"NNNNN"` under **`DUEL_GAMBLE_REVEALED_EVENTS.transitions`** (not `DUEL_RESOLVE_MANEUVER_EVENTS`) → custom GameState (e.g. `DUEL_CHOOSE_GAMBLE_CARD_NNNNN` / id `5270NNNNN`).
4. Named transitions must match `actGambleCardChosen`: `"useManeuver"` → `DUEL_USE_MANEUVER_FROM_COMBAT_CARD`, `"noManeuver"` → `DUEL_CHOOSE_GAMBLE_CARD_EVENTS`. No `actBack` (gamble already committed).

**Transition priority:** `EventTransition` defaults to priority 8; reaction transitions use priority 6. So Ivy-style "before choosing" reactions (`Reaction_02042`) still run first — do not `stackEvent` the choose transition ahead of them.

**Framework: deck = actor, not active player.** When the Maneuver owner is active but the gamble is the adversary's:
- `argsDuelChooseGambleCard` / `actGambleCardChosen` must read/write the **duel-round actor's** faction deck (`getDuelRoundActor()->ControllerId`), not `getActivePlayerId()`.
- Before `nextState("useManeuver"|"noManeuver")`, `changeActivePlayer($actor->ControllerId)` so combat-card maneuvers belong to the gambler.

**Public reveal for the stolen-chooser state:** Prefer public `cards` (everyone + spectators) via `getArgsFromManeuver` + State `argsForState()` (01077 shape — client path `args.args.args.cards`). Do **not** park a one-off helper on `ArgumentsTrait`. Stock `duelChooseGambleCard` stays `_private.active` for normal gambles. Select/Confirm only when `isCurrentPlayerActive()`.

**Args / act live on the Maneuver:** `getArgsFromManeuver` for the choose state; `actFromManeuverWithId` clears the lock then calls `$game->actGambleCardChosen($id)`. Clear also on adversary `EventDuelEndOfRound`, owner `EventDuelNewRound`, cancel, duel end.

References: `Maneuver_03047a` / `Maneuver_03047b` (Proper Drama), `Technique_02037` (cannot-gamble Technique shape), `Maneuver_01108a`/`b` (dual a/b Maneuvers), `Maneuver_01077` (`getArgsFromManeuver` + public `cards` + `argsForState`).

### Pure-calc maneuvers (no `EventResolveManeuver` needed)

When the maneuver only adds/subtracts stat values and has no one-shot side effect (no draw, no wound, no transition), implement **only** the `EventDuelCalculateManeuverValues` branch and skip `EventResolveManeuver` entirely. The framework still rolls back the calc on cancel, and there's nothing to resolve. Reference: `Maneuver_03011` ("control X at duel location" → `+1 Riposte`).

### Pure-resolve maneuvers (no calc branch)

When the maneuver has **only** a one-shot side effect (wound adversary, draw, move Home, …) and no Riposte/Parry/Thrust change, implement **only** `EventResolveManeuver` and skip `EventDuelCalculateManeuverValues`. Still include the `// EventManeuverCanceled handler not needed` comment. Call `$theah->eventCheck($woundEvent)` before `queueEvent` for wound effects.

```php
if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
{
    $owner = $this->getOwningCard($event->theah);
    $adversary = $event->theah->getDuelRoundOpponent();
    $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
        $adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
    );
    $event->theah->eventCheck($woundEvent);
    $event->theah->queueEvent($woundEvent);
}
```

Reference: `Maneuver_03033` (Glorious — Gambling + Influence ≥ → wound), `Maneuver_01055` (Ranged Weapon → wound), `Maneuver_01033` (Influence > → move adversary Home).

### Pattern C.2 — Suppress end-of-round threat→wound conversion (with optional carry-forward)

"Your participant's threat is not converted to wounds this round" — the **threat-to-wound conversion** happens once per round inside `StatesTrait::stDuelEndOfRound`, NOT continuously during the round. Trying to gate this off `EventDuelCalculateCombatCardStats` or anywhere mid-round is the wrong hook. Three things you have to know to wire it correctly:

#### 1. The conversion mechanics

`stDuelEndOfRound` (StatesTrait.php:~1414) does, in order:

1. **Reads** `duel_round.ending_<actor>_threat` (plus the `<side>_threat_is_lethal` flag).
2. **Wipes** both fields to `0` via direct SQL (StatesTrait.php:~1453) — this is critical: by the time anything else runs, the threat is *gone* from the DB row.
3. Computes `$wounds = $threat`, possibly reduced by Restricted Hostilities (stat cap when non-lethal).
4. **Queues** `EventCharacterBeingWounded($actor->Id, $adversary->Id, $wounds, $reason)` (StatesTrait.php:~1492).
5. Queues `EventDuelEndOfRound`.

So any maneuver that wants to suppress this conversion gets its window when `EventCharacterBeingWounded` fires.

#### 2. Identifying THIS wound event

`EventCharacterBeingWounded` is also fired by many other things (other wound effects, maneuvers, techniques). The conversion event has a unique signature: **`$event->characterId == actor.Id && $event->sourceId == adversary.Id`** — that pairing only happens for the end-of-round threat→wound conversion. Gate on it:

```php
if ($event instanceof EventCharacterBeingWounded && $this->IsActive)
{
    $theah = $event->theah;
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $event->characterId != $actor->Id) return;

    $adversaryId = $theah->getDuelOpponentId($actor->Id);
    if ($event->sourceId != $adversaryId) return;
    // ... safe to suppress
}
```

#### 3. Carrying the threat forward (`PENDING_<side>_THREAT`)

If the card text rolls the suppressed threat into next round, **don't** try to keep `ending_<actor>_threat` populated — the SQL wipe (step 2 above) zeroed it before the wound event was even queued, so reading it back gives 0.

The supported channel is the `PENDING_CHALLENGER_THREAT` / `PENDING_DEFENDER_THREAT` globals. `stDuelNewRound` reads them at StatesTrait.php:~1130–1144, adds them to the next round's starting threat, and deletes them. Capture the wound amount **before** zeroing, route to the right side via `getDuelChallengerId()`:

```php
$carryOver = $event->wounds;
if ($carryOver <= 0) return;
$event->wounds = 0;

$game = $theah->game;
$challengerId = $theah->getDuelChallengerId();
if ($actor->Id == $challengerId)
{
    $pending = $game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0);
    $game->globals->set(Game::PENDING_CHALLENGER_THREAT, $pending + $carryOver);
}
else
{
    $pending = $game->globals->get(Game::PENDING_DEFENDER_THREAT, 0);
    $game->globals->set(Game::PENDING_DEFENDER_THREAT, $pending + $carryOver);
}
```

Reference: `Maneuver_02039` (Add Threat — adds +1 to both sides on the next round's pool). `Maneuver_03023` (Second Wind — captures the suppressed conversion amount).

#### 4. Also zero `duel_round.wounds_taken`

Zeroing `$event->wounds` stops the wound from being applied to the character row, but **`duel_round.wounds_taken` was already incremented during the round** by `DB::updateRoundThreats` (DB.php:~539–552) as a running "wounds the actor is about to take" tally. If you don't reset it:

- The `updateRoundThreats` notification still ships the inflated count to the client (EventHub.php:~2197, ~2223) — UI displays a wound count that never happened.
- `Theah::duelParticipantWoundsTaken()` sums `wounds_taken` across the participant's **prior** rounds. `Maneuver_01107` reads that aggregate. With the suppression in place but the column unchanged, downstream cards see wounds that never landed.

Add the row update inside the same `EventCharacterBeingWounded` branch:

```php
$duelId = $game->globals->get(Game::DUEL_ID);
$round = $game->globals->get(Game::DUEL_ROUND);
$game->DbQuery("UPDATE duel_round SET wounds_taken = 0 WHERE duel_id = $duelId AND round = $round");
```

#### 5. "Adversary absent" predicate

When the suppression has an "unless adversary absent" gate, both of these are valid; using both is cheap and explicit:

```php
$adversary = $theah->getCharacterById($adversaryId);
if ($theah->game->characterIsInDiscardOrLocker($adversary)
    || $adversary->Location != $actor->Location)
{
    return;   // adversary absent — let wound resolve normally
}
```

Destroyed characters have `Location` set to `"Locker-…"`/`"Discard-…"`, so the location-mismatch check subsumes the destroyed case, but `characterIsInDiscardOrLocker` is the canonical destroyed test (memory feedback) and reads cleanly.

#### 6. Lethality is not preserved across the rollover

There is no `PENDING_<side>_THREAT_IS_LETHAL` global. If the suppressed threat was lethal, the rolled-over threat lands non-lethal. `Maneuver_02039` has the same limitation. If a future card's text requires preserving lethality across rounds, the right move is to add the global rather than special-case it in the card.

#### 7. State tracking on the Maneuver

Use a `public bool $IsActive` field on the Maneuver, set on `EventResolveManeuver`, cleared on `EventManeuverCanceled` and `EventDuelEndOfRound`. Mark `$owner->IsUpdated = true` whenever you flip it so the framework persists. The `EventDuelEndOfRound` reset is needed because the maneuver instance lives on `$theah->cards` across rounds — without resetting, the next round's conversion would also be suppressed.

References: `Maneuver_03023` (Second Wind — full pattern with carry-forward), `Maneuver_02039` (Add Threat — `PENDING_*_THREAT` write-only producer side).

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

### Pattern C.3 — Choice-at-activation Maneuver (same player picks how the calc applies)

For "Maneuver: [gate] • +X [stat A] or +X [stat B]" (and similar "pick one of two effects" shapes where the chooser is the maneuver's own controller, not the adversary), prompt the player **at activation time** (before the calc phase) and store the choice on the Maneuver object so the calc-event branch can read it.

Wire it as:

1. **Private choice field** on the Maneuver (e.g., `private bool $ChooseParry = false;`). Reset it in `EventManeuverCanceled` — the maneuver instance lives on `$theah->cards` across rounds, so without reset the next activation would default to the prior choice. (For two-branch choices, also reset in `__construct` to make the default explicit.)
2. **`EventManeuverActivated` handler** — `stackEvent` (not `queueEvent`) a `createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id)`. `stackEvent` is what makes the choice prompt fire *before* the calc-phase events.
3. **GameState class** `State_duelResolveManeuver_NNNNN` under `modules/php/States/<expansion>/`:
   - `id: States::DUEL_RESOLVE_MANEUVER_NNNNN` (constant value `52500000 + NNNNN`, prefix `5250` — NOT `4` or `5290`).
   - `name: "duelResolveManeuver_NNNNN"`.
   - `transitions: ["" => States::DUEL_RESOLVE_MANEUVER_EVENTS]` (empty default — `actFromManeuverWithId` calls `$game->gamestate->nextState()` with no arg).
   - Possible action: `actFromCardWithId(string $id)` → `$this->game->actFromCardWithId($id)`.
   - `zombie(int $playerId)` → `$this->game->gamestate->nextState()`.
4. **`states.inc.php` wiring** — add `"NNNNN" => States::DUEL_RESOLVE_MANEUVER_NNNNN` to `DUEL_RESOLVE_MANEUVER_EVENTS.transitions` (NOT `HIGH_DRAMA_PLAYER_TURN_EVENTS`).
5. **`States.php`** — `const DUEL_RESOLVE_MANEUVER_NNNNN = 525<NNNNN>;` (alphabetize within the `DUEL_RESOLVE_MANEUVER_*` block).
6. **Override `actFromManeuverWithId(Game $game, int $state, string $stateName, int $id)`** — branch on `$state == States::DUEL_RESOLVE_MANEUVER_NNNNN`, set the choice field, mark `$owner->IsUpdated = true`, emit a `notify->all("message", ...)` so the log records which branch was picked, then `$game->gamestate->nextState()` (no arg — `""` is the default key the GameState transitions table uses).
7. **`EventDuelCalculateManeuverValues` branch on the stored field** — if/else over the choice; each branch mutates the appropriate field (`$event->parry`, `$event->thrust`, etc.) and pushes an `$event->explanations[]` line via `$owner->getInjectCode()`.
8. **JS `OnUpdateActionButtons.<expansion>.js`** — under `methods`, add `'duelResolveManeuver_NNNNN': () => { ... }` with one button per choice. Use `addActionButton(btnId, _('Label'), () => this.bgaPerformAction('actFromCardWithId', { id: N }))` — no confirm step, no card chooser. Mirror the buttons in `OnEnteringState`/`OnLeavingState` only if you need highlighting; the simple two-button case skips both.

```php
// Maneuver_NNNNN
private bool $ChooseParry = false;

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
    {
        $owner = $this->getOwningCard($event->theah);
        $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id);
        $event->theah->stackEvent($transition);
    }

    if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
    {
        $owner = $this->getOwningCard($event->theah);
        if ($this->ChooseParry) { $event->parry += 2; $event->explanations[] = sprintf(/* … */); }
        else                    { $event->thrust += 2; $event->explanations[] = sprintf(/* … */); }
    }

    if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
    {
        $this->ChooseParry = false;
        $this->getOwningCard($event->theah)->IsUpdated = true;
    }
}

public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
{
    parent::actFromManeuverWithId($game, $state, $stateName, $id);
    if ($state == States::DUEL_RESOLVE_MANEUVER_NNNNN)
    {
        $this->ChooseParry = ($id == 1);
        $this->getOwningCard($game->theah)->IsUpdated = true;
        // emit notify->all("message", ...)
    }
    $game->gamestate->nextState();
}
```

#### Why `EventManeuverActivated` (not `EventResolveManeuver`)

`EventResolveManeuver` fires after the round's stat calc, when one-shot side effects land (wounds, draws, etc.). If the player's choice *drives the calc*, you must capture it before calc runs — that's `EventManeuverActivated`, which fires earlier in the activation sequence. Queuing a transition from `EventResolveManeuver` for a calc-driving choice lands the prompt after calc has already happened, and the choice has no effect.

`EventResolveManeuver` remains the right hook for one-shot side effects that don't influence calc (wound adversary, draw, queue an end-of-round sub-state). The C.3 pattern is specifically for "the choice changes the math."

#### `stackEvent`, not `queueEvent`, on the activation transition

Same priority math as Pattern D.3: `queueEvent` at `MEDIUM_PRIORITY = 3` would land *behind* calc-phase events. `stackEvent` assigns `min(pending priorities) - 1`, guaranteeing the choice prompt fires first.

**Multi-step C.3 (character chooser → then Riposte/Thrust buttons, etc.):** `stResolveManeuverFromCombatCard` queues Activate → Resolve → Calculate **up front**. The first `stackEvent` from `EventManeuverActivated` correctly pre-empts that pending calc. But when state 1 finishes and transitions to state 2, **that second transition must also `stackEvent`** — `queueEvent("NNNNN_2")` lands *behind* the still-pending `EventDuelCalculateManeuverValues`, so calc runs with the default choice flag and the later button press looks like "stats never updated."

Do **not** "fix" this by re-emitting `EventDuelCalculateManeuverValues` after the choice. Fix the ordering: `stackEvent` every intermediate transition until the calc-driving choice is stored. After the final choice, `nextState()` resumes EVENTS and the original pending calc reads the stored flag.

#### Pure-calc variant: no `EventResolveManeuver` handler needed

When both branches are pure stat mutations (no wound / draw / transition), skip `EventResolveManeuver` entirely. The calc-event branch on the stored choice is the entire effect. Reference: `Maneuver_03024` (both branches are +2 stat).

#### Choice-with-side-effect variant: queue side effects in `actFromManeuverWithId`

When one branch has a side effect (wound the adversary, draw, etc.), queue those events directly from `actFromManeuverWithId` after recording the choice — don't defer to `EventResolveManeuver`. The Maneuver is mid-activation; events queued here land at the right point in the activation sequence. Reference: `Maneuver_01135` (branch 2 queues `createCharacterBeingWoundedEvent` from `actFromManeuverWithId`).

#### Wound-your-other-character cost + choice (multi-step C.3)

"Maneuver: Wound your other character at this location • +1 Riposte or +2 Thrust" (and similar cost • or-choice shapes):

1. **`isAvailableToPlayer`** — at least one other character you control at `$actor->Location` (`getCharactersAtLocationByPlayerId`, exclude actor).
2. **State 1** (`duelResolveManeuver_NNNNN`) — friendly character chooser (`IAbilityThatTargetsCharacters` on the Maneuver; `IRiskThatTargetsCharacters` on the Risk). JS: `highlightCardsAsSelectable` + confirm, same shape as `highDramaPhase03034` / `duelResolveManeuver_01051`.
3. **State 1 → 2** — save `$WoundTargetId`, **`stackEvent`** transition `"NNNNN_2"` (not `queueEvent`).
4. **State 2** (`duelResolveManeuver_NNNNN_2`) — Riposte/Thrust (or Parry/Thrust) buttons. On choice: set the calc flag, queue the wound via `createCharacterBeingWoundedEvent` + `eventCheck`, `nextState()`.
5. **Calc** — `EventDuelCalculateManeuverValues` branches on the stored flag (same as single-step C.3).

Reference: `Maneuver_03035` (Loyal).

#### State-tracking discipline

If the maneuver has any cross-round state beyond the choice (a `next-round` modifier, an `IsActive` flag), reset it in both `EventManeuverCanceled` AND `EventDuelEnd` (and `EventDuelEndOfRound` for "next round only" effects). The choice field itself only needs `EventManeuverCanceled` reset — the next activation will overwrite it. Multi-step: also clear `$WoundTargetId` (etc.) on cancel.

References: `Maneuver_01135` (template; choice gates a side-effect branch with cross-round Thrust reduction), `Maneuver_03024` (Superstitious — pure-calc Sorcerer/Monster gate variant), `Maneuver_03035` (Loyal — multi-step wound cost + Riposte/Thrust choice).

### Pattern C.1 — Final Strike maneuver (post-death effect; optionally with player choice)

"Final Strike • <effect>" activates **when your participant is destroyed the round this card is played.** Two shapes:

- **Pure-data on-death** (no player input): mutate threat / queue draw / fire notify. Reference `_01082` (A Heroic End — `+2 Threat` + Lethal to adversary when participant dies).
- **On-death with player choice** (en garde a target, pick a card to discard, etc.): queue a `createTransitionEvent` from the destroyed handler into a card-specific sub-state. Reference `_03022` (Overzealous — En Garde target at the location + conditional draw if participant was Zealot/Hunter).

Skeleton (player-choice variant):

```php
private int $FinalStrikeParticipantId = 0;
private string $DuelLocation = "";   // capture at resolve time — see "Destroyed character is in the locker" below.

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
    {
        $owner = $this->getOwningCard($event->theah);
        $this->FinalStrikeParticipantId = $event->theah->getDuelOpponentId($event->adversaryId);
        $participant = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
        $this->DuelLocation = $participant->Location;
        $owner->IsUpdated = true;
    }

    if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->FinalStrikeParticipantId)
    {
        $game = $event->theah->game;
        if (! $game->globals->get(Game::IN_DUEL)) return;

        $character = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
        $owner = $this->getOwningCard($game->theah);
        $playerId = $character->ControllerId;   // still valid mid-destroy — see ControllerId note below.

        // ... conditional pure-data effects (draw, notify) ...

        $transitionEvent = EventFactory::createTransitionEvent($playerId, $owner->Id, "NNNNN", $this->Id);
        $event->theah->queueEvent($transitionEvent);
    }

    if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
    {
        $this->FinalStrikeParticipantId = 0;
        $this->DuelLocation = "";
        $owner = $this->getOwningCard($event->theah);
        $owner->IsUpdated = true;
    }

    if ($event instanceof EventDuelNewRound && $this->FinalStrikeParticipantId != 0)
    {
        $owner = $this->getOwningCard($event->theah);
        $owner->IsUpdated = true;
        $this->FinalStrikeParticipantId = 0;
        $this->DuelLocation = "";
    }
}
```

#### Destroyed character is in the locker by selection time — capture `$DuelLocation` at resolve

By the time the player makes the en-garde / discard / etc. choice, **the destroyed participant has been moved to the locker.** `$actor->Location` and `$theah->getDuelRoundActor()->Location` will return the locker location, NOT the duel location. Any `getCharactersAtLocation($actor->Location)` query that runs in `getArgsFromManeuver` / `isValidTargetForAbility` / `actFromManeuverPass` will look at the wrong location and return an empty (or wrong) target set.

Capture `$participant->Location` on `EventResolveManeuver` (when the participant is still alive at the duel location), store it on the Maneuver object, and route all post-death location queries through it. Reset alongside `$FinalStrikeParticipantId` in `EventManeuverCanceled` and `EventDuelNewRound`. Mirror `_03022::DuelLocation` + `getResolutionLocation(Theah $theah)` helper.

#### Mid-destroy character lookups are valid

When `EventCharacterDestroyed` fires, the character has `IsDying = true` but has NOT yet been physically moved (the destroy event is queued, not applied; the move happens in the central hub handler later in the same loop). So inside your `EventCharacterDestroyed` handler:

- `$theah->getCharacterById($event->characterId)` returns the character.
- `$character->ControllerId` is still the original controller (use this to pick the player who will make the post-death choice — NOT `getActivePlayerId()`, which is the current duel actor and may be the *killer*, not the controller of the destroyed participant).
- `$character->hasTrait(...)` works for conditional gates ("if your participant was a Zealot or Hunter").

#### State naming: `DUEL_END_OF_ROUND_NNNNN`, not `DUEL_RESOLVE_MANEUVER_NNNNN`

The maneuver's "resolve" phase already ran (that's where you queued the participant-tracking). The transition into the player-choice state fires from `EventCharacterDestroyed` during the **end-of-round events loop** (state `5290` `DUEL_END_OF_ROUND_EVENTS`), because that's where wound-driven destruction usually completes. Wire accordingly:

- **States.php constant:** `DUEL_END_OF_ROUND_NNNNN = 52901NNN` (or `52903NNN` for faf, etc. — pattern is `5290` + zero-padded card number).
- **`states.inc.php`:** add `"NNNNN" => States::DUEL_END_OF_ROUND_NNNNN` to the `DUEL_END_OF_ROUND_EVENTS.transitions` map (NOT to `DUEL_RESOLVE_MANEUVER_EVENTS`). If you wire it under `DUEL_RESOLVE_MANEUVER_EVENTS` you'll get a runtime `transition NNNNN is impossible at this state (5290)` error.
- **GameState class:** `modules/php/States/<expansion>/State_duelEndOfRound_NNNNN.php`, `name: "duelEndOfRound_NNNNN"`, `transitions: ["" => States::DUEL_END_OF_ROUND_EVENTS]`. Mirror `State_duelEndOfRound_01096`.
- **JS handlers:** all three `On*.<expansion>.js` files keyed `'duelEndOfRound_NNNNN'`. Use private args (`args.args._private.args.characterIds`) — the state should use `argsForStatePrivate`.

Why end-of-round specifically: wounding during combat resolution carries the death over into `DUEL_END_OF_ROUND_EVENTS` for final processing. The transition event you queued from `EventCharacterDestroyed` is dequeued there; only states whose `transitions` map declares the transition string can accept it.

#### Pass button + gate-on-pass for "target if able" prompts

"En garde target character at this location" (and similar) is a do-if-able prompt — there may be no valid target (everyone at the location is already en garde, no characters at the location, etc.). Wire a Pass affordance:

- **GameState class** — declare `actFromCardPass` as a second `#[PossibleAction]`:
  ```php
  #[PossibleAction]
  public function actFromCardPass(): void { $this->game->actFromCardPass(); }
  ```
- **Maneuver** — override `actFromManeuverPass` and **throw `UserException` if valid targets exist** (player cannot pass when they have a legal choice); otherwise notify + `$game->gamestate->nextState()`:
  ```php
  public function actFromManeuverPass(Game $game, int $state): void
  {
      parent::actFromManeuverPass($game, $state);
      if ($state == States::DUEL_END_OF_ROUND_NNNNN)
      {
          $location = $this->getResolutionLocation($game->theah);
          if (count($this->getValidTargets($game->theah, $location)) > 0)
              throw new UserException($game->translate("There are targets — you must choose one."));
          $owner = $this->getOwningCard($game->theah);
          $game->notify->all("message", clienttranslate('${maneuver_inject_code}: No valid target.'), [
              "maneuver_inject_code" => $owner->getInjectCode(),
          ]);
          $game->gamestate->nextState();
      }
  }
  ```
- **JS `OnUpdateActionButtons`** — add the alert-color Pass button alongside the Confirm button:
  ```js
  this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
  ```

The gate is what keeps the Pass button honest — without it, a player could skip a mandatory effect by clicking Pass. Mirror `_03022::actFromManeuverPass`.

### Cross-player maneuver sub-state (adversary picks something)

When the maneuver effect requires the **opposing** controller to pick (e.g., "they discard a card from their hand"), queue a `createTransitionEvent($adversary->ControllerId, ...)` from `EventResolveManeuver`, register the new state in `states.inc.php` under the Duel resolve-maneuver transitions, and implement `actFromManeuverWithId` to validate the pick. Reference: `Maneuver_01115` (Taunt — Finesse-gated adversary-discards-a-card flow), `Maneuver_03036` (line-count-gated discard — Pattern C.4).

**Empty hand:** never enter the chooser with zero hand cards. If discard is the *only* effect, gate `isAvailableToPlayer` on `count(hand) > 0` (`Maneuver_01108a`). If discard is conditional on top of a still-useful calc (C.4), skip the transition at resolve instead — see Pattern C.4.

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

References: `Reaction_01080` (Iron Reply-style — adds Parry during opposing maneuver), `Reaction_01140`, `Reaction_01088`, `Reaction_02048` (Pressure-to-cancel — multi-event family, saved-event re-emit on decline), `Reaction_03010` (cross-player choice flow after pay — see Pattern D.1), `Reaction_03031` (effect-event redirect after pay — see Pattern D.4; structural cousin of `Reaction_02016` on attachments).

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

### Pattern D.2 — Single-stage RiskReaction with pay that applies an effect

When the RiskReaction's effect is a single-shot resolution after pay — mutate a global (`CHALLENGE_STAT`), engarde a fixed character, set a flag, etc. — with no cross-player choice and no second transition, the shape collapses to:

1. **`handleEvent` on the trigger event** → gate, store any ids needed for the effect/notify on the reaction object, queue `createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id)`.
2. **`performReaction('use')`** → queue `createEnteringPayStateEvent(PAY_STATE_IN_HAND_REACTION)` + `createReactionPayTransitionEvent`. Don't apply the effect here.
3. **`handleEvent` on `EventRiskReactionTriggered && internalId == $this->Id`** → apply the effect, emit any Sorcerer start/played events, notify, `setUsed`.

**WHY apply the effect in `EventRiskReactionTriggered` and not directly in `performReaction('use')`:** between `performReaction('use')` and the post-pay trigger event, framework cancel-reactions (Hexenjagd-style) can fire and cancel the ability. If you mutated `CHALLENGE_STAT` or queued `createCardEngardedEvent` inside `performReaction('use')`, that side effect would persist even when the Risk play is canceled. Deferring keeps the side effect paired with the resolved pay. Mirror `Reaction_03012` for this discipline; `Reaction_03010` follows the same pattern for its multi-stage variant; `Reaction_03046a`/`b` use it for engarde.

This is single-stage so no `$stage` field is needed. Save only the ids you'll reference inside `EventRiskReactionTriggered` (e.g., `performerId` / `intervenerId` for engarde or Sorcerer events).

**En garde after pay:** queue `createCardEngardedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id)` only when `$character->Engaged` is still true at resolve time (engarde is a no-op otherwise). No character chooser → no `IRiskThatTargetsCharacters`.

References: `Reaction_03012` (Subtle — flips `CHALLENGE_STAT`), `Reaction_03046a` / `Reaction_03046b` (Passionate — engarde intervener / challenger).

### Pattern D.2.1 — Pressure-total RiskReaction ("Add +1 to your total for the pressure")

When the printed text says **When a pressure occurs … Add +1 to your total for the pressure** (optionally gated on board position / character counts), wire Pattern D.2 against `EventPressureOccuring`, then apply the bonus inside `UtilitiesTrait::pressureLocation()` — the same channel Solomonia (`_02044`) and Trial of Faith (`Reaction_02019`) use.

1. **Trigger** — `EventPressureOccuring && isAvailable()`, hand guard `Location == Game::LOCATION_HAND`, plus any printed gate (e.g. Loyal: count non-Mercenary controlled characters at `$event->location`; owner's count must be strictly `>` each other controller's count at that location — players with 0 non-Mercs there count as 0).
2. **Pay** — standard `performReaction('use')` → `EnteringPayState` + `ReactionPayTransition`.
3. **Effect in `EventRiskReactionTriggered`** — `setGlobalFlag(PRESSURE_TYPE, <NEW>_PRESSURE_TYPE)` and `globals->set(<NEW>_PLAYER_ID, $owner->ControllerId)` (or a card-id global if you need to look up the controller later, like `SOLOMONIA_ID`). `setUsed`. Notify.
4. **`pressureLocation()` branch** — outside the `foreach ($pressureStats)` loop (so it applies once to "the pressure," not once per pressure type / not Influence-only):

```php
if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::<NEW>_PRESSURE_TYPE))
{
    $playerId = $this->globals->get(Game::<NEW>_PLAYER_ID, 0);
    if ($playerId && isset($playerInfluences[$playerId]))
    {
        $playerInfluences[$playerId]['influence'] += 1;
    }
}
```

5. **Cleanup** — delete the player-id global wherever `PRESSURE_BONUS` / `PRESSURE_TYPE` are reset (`StatesTrait` post-pressure cleanup).

**Do not reuse `Game::PRESSURE_BONUS`.** That global is only read under `PACK_TACTICS_PRESSURE_TYPE` and only when the pressure stat is Influence (`Action_01028`). Loyal-style "+1 to your total for the pressure" is any pressure type and any reacting player — mint the next binary flag (`8192`, `16384`, … after `USSURAN_INTRIGUE_PRESSURE_TYPE = 4096`) plus a dedicated player-id global.

**WHY defer to `EventRiskReactionTriggered`:** same as D.2 — cancel-during-pay must not leave a dangling pressure flag.

References: `Reaction_03035` (Loyal), `_02044` (Solomonia — passive auto-flag on `EventPressureOccuring`, no pay), `Reaction_02019` (Trial of Faith — RiskReaction that sets `TRIAL_OF_FAITH_PRESSURE_TYPE`).

### Pattern D.3 — RiskReaction that cancels pending high-priority events in a batch

When the printed text says "Cancel the movement" / "Cancel the [effect]" and the effect being canceled is delivered by **already-queued, high-priority events** (e.g. `EventRenownAddedToLocation` + `EventRenownRemovedFromLocation` with shared `batchId` — see `_01117`, `_01062`, `_01150` for the producer side), the naive Pattern D.2 shape will deadlock on event ordering. Wire it as:

1. **`implements ICancelReaction`** on the Reaction class. The marker interface has no required methods for the cancel case (`revertCancellation` is only invoked by `Reaction_01109` Not Today against `_01140` specifically). The framework checks `instanceof ICancelReaction` in `FrameworkActionsTrait::actChooseCardForReactionPaid` and **flips both `EventRiskReactionTriggered` and `EventRiskPlayed` from `queueEvent` to `stackEvent`** for the post-pay step.
2. **`handleEvent` on the trigger event** → gate, save `$event->batchId ?? 0` on the reaction object, **`stackEvent`** the reaction transition (not `queueEvent`) so it pre-empts the rest of the still-pending batch.
3. **`performReaction('cancel')`** → **`stackEvent`** the pay events (`createReactionPayTransitionEvent` first, then `createEnteringPayStateEvent` — LIFO means the EnteringPayState dequeues first). Don't apply the effect here. `'decline'` → reset saved state, nothing else.
4. **`handleEvent` on `EventRiskReactionTriggered && internalId == $this->Id`** → apply the cancel by deleting the targeted queued events, notify, `setUsed`, reset saved state.

**WHY `ICancelReaction` is load-bearing here, not optional:** without it, the post-pay `EventRiskReactionTriggered` is `queueEvent`'d at `MEDIUM_PRIORITY = 3`. The companion `Added`/`Removed` events sit at `HIGH_PRIORITY = 2` (lower number = higher priority — `getNextEvent` orders by `event_priority` ASC). They dequeue and apply the Renown change *before* your trigger handler runs. With `ICancelReaction`, both post-pay events are `stackEvent`'d, which assigns `min(current priorities) - 1` — guaranteeing they pre-empt every queued high-priority event including the ones you need to delete.

**WHY also `stackEvent` the reaction transition and pay events:** same priority math at every step. Anything you `queueEvent` lands at `MEDIUM_PRIORITY` (the event constructor default) and queues *behind* the `HIGH_PRIORITY` batch members. The user would never get the offer; the Renown move would resolve first. Use `stackEvent` for every event you want to interleave ahead of the pending batch.

**Prefer targeted helpers over `deleteEventBatch` for batch members.** `deleteEventBatch($batchId)` is type-agnostic — fine when you genuinely want to delete every batch member, but the Pattern D.3 contract is "cancel these specific events." Add (or use) `DB::deleteXEventsByBatchId(int $batchId)` helpers (mirror `deleteRenownAddedToLocationEventsByBatchId` / `deleteRenownRemovedFromLocationEventsByBatchId`) and call them from a `Theah` pass-through. Anchor the batchId substring with a trailing semicolon — `'%batchId";i:5;%'` — so `batchId=5` doesn't false-match `batchId=50/51/…` (the bare `deleteEventBatch` has this prefix-collision; the targeted helpers should not).

**`EventRenownMovingBetweenLocations` is informational only** — it has no `EventHub` handler, so canceling/deleting it does nothing on its own. The actual Renown state change is in the `Added`/`Removed` pair queued alongside it with shared `batchId`. To cancel a Renown movement, delete those two; ignore the Moving event itself (it's already been dequeued and processed by the time you reach `EventRiskReactionTriggered`, anyway).

References: `Reaction_03020` (Commanding — Leader Reaction canceling Renown movement from Leader's location); the related but simpler `Reaction_01140` (Stubborn — `ICancelReaction` that cancels an `EventCardMoving` in-place via `$event->canceled = true` + saved-event re-emit on decline, no post-pay batch deletion needed).

### Pattern D.4 — RiskReaction that redirects wound/move/engage effects to another character

When the printed text says an **opponent's ability would wound, move, or engage your character** and a **performer at that location** suffers the effect instead, wire a clone-cancel-reemit redirect adapted from `Reaction_02016` (Cross of the Martyrs / Diplomatic Impunity) but as a **`RiskReaction`** with effect-based triggers.

**Do not copy 02016's trigger gate blindly.** `Reaction_02016` is an `AttachmentReaction` for "redirect a **targeted** ability" — its `shouldReactToEvent` requires `IAbilityThatTargetsCharacters`. Card text that names **effects** ("would wound, move, or engage") and never says "target" must intercept the **effect events themselves**, including abilities that never implement `IAbilityThatTargetsCharacters` (maneuvers, forced wounds, engage-without-chooser, etc.).

#### Trigger events (map printed verbs → events)

| Printed verb | Event | Target id field |
|---|---|---|
| wound | `EventCharacterBeingWounded` | `$event->characterId` |
| move | `EventCardMoving` | `$event->cardId` |
| engage | `EventCardEngaged` | `$event->cardId` |

Add `EventCharacterIntervened` when duel intervention should be redirectable (copy 02016's intervene branch: your character is `$event->newTargetId`, swap `DUEL_DEFENDER` in `releaseEvent`). **Do not** wire heal / `EventCharacterTargeted` / challenge-issued / engarde unless the printed text names those verbs — 02016's broader event set is for general "redirect targeted ability" attachments.

#### Gate shape

```php
private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId, ?int $targetCharacterId): bool
{
    $owner = $this->getOwningCard($theah);
    if ($owner === null) return false;
    if (! $this->isOpponentAbility($theah, $sourceId, $abilityId, $owner->ControllerId)) return false;

    $target = $theah->getCharacterById($targetCharacterId);
    if ($target === null || $target->ControllerId != $owner->ControllerId) return false;

    // "Performer at that location" — another of your characters at the same spot
    $others = $theah->getCharactersAtLocationByPlayerId($target->Location, $owner->ControllerId);
    $others = array_filter($others, fn($c) => $c->Id != $target->Id);
    if (count($others) === 0) return false;

    $this->savedSourceId = $sourceId;
    $this->savedAbilityId = $abilityId;
    return true;
}

private function isOpponentAbility(Theah $theah, int $sourceId, string $abilityId, int $ownerPlayerId): bool
{
    $source = $theah->getCardById($sourceId);
    if ($source) {
        return $source->ControllerId != $ownerPlayerId && $source->ControllerId != 0;
    }
    $action = $theah->getInPlayActionById($abilityId);
    if ($action && $action instanceof ICardAbility) {
        $owningCard = $action->getOwningCard($theah);
        return $owningCard !== null
            && $owningCard->ControllerId != $ownerPlayerId
            && $owningCard->ControllerId != 0;
    }
    return false;
}
```

**WHY `isOpponentAbility` checks both `sourceId` and in-play action owner:** wound/move/engage events carry `$event->sourceId` (the card that queued the effect) and `$event->abilityId`. Maneuvers and other non-targeting abilities may have no `IAbilityThatTargetsCharacters` implementation but still emit these events with valid source/ability ids.

#### "Performer at that location"

Same mechanical meaning as Hexenjagd (`Reaction_01053`): `getCharactersAtLocationByPlayerId($target->Location, $owner->ControllerId)`, **excluding** the character currently being wounded/moved/engaged. The player picks which other character takes the hit via `redirect-{id}` buttons in `getReactionButtonProperties()`. No `IRiskThatTargetsCharacters` on the Risk class — the Reaction's button UI is the chooser.

#### Clone-cancel-reemit flow (Risk pay split)

1. **`handleEvent` on trigger** — clone the pending event, `$event->canceled = true`, save `$targetCharacterId`, `queueEvent(createReactionTransitionEvent(...))`. Hand guard: `if (! ($owner->Location == Game::LOCATION_HAND)) return;`
2. **`performReaction('redirect-{id}')`** — queue pay only (`createEnteringPayStateEvent(PAY_STATE_IN_HAND_REACTION)` + `createReactionPayTransitionEvent`). **Do not** `releaseEvent` or `setUsed` here.
3. **`handleEvent` on `EventRiskReactionTriggered && internalId == $this->Id`** — notify, then redirect:
   - If `loadAbility()` returns `IAbilityThatTargetsCharacters` → `isValidTargetForAbility` enforces "(If they are able)"; invalid → cancel + message.
   - **Else** → `releaseEvent($characterId)` directly (non-targeting abilities).
   - `setUsed` here (Risk is already in discard from pay).
4. **`performReaction('decline')`** — mirror 02016: only re-`releaseEvent` to the original target for `EventCharacterIntervened` (with `$skipNextEvent = true`); other saved events stay canceled.

`releaseEvent()` mutates the cloned event's target field (`characterId` / `cardId`) and re-queues it. For intervention, also swap `DUEL_DEFENDER` and set `CHOSEN_TARGET` — copy verbatim from `Reaction_02016`.

**WHY defer `releaseEvent` to `EventRiskReactionTriggered`:** same discipline as Pattern D.2 — the Risk must be paid before the redirect lands; framework cancel-reactions during pay should not re-emit a redirected event if the Risk is declined mid-pay.

**Do not copy 02016's wound-on-redirect** unless the card text says so — Cross of the Martyrs wounds the redirect target 1; Altruistic does not.

#### 02016 (AttachmentReaction) vs 03031 (RiskReaction) — when to use which pattern

| | `Reaction_02016` (attachment) | `Reaction_03031` (Risk) |
|---|---|---|
| Base | `AttachmentReaction` — equipped character is the protected target | `RiskReaction` — any of your characters; Risk in hand is the cost |
| Trigger gate | Requires `IAbilityThatTargetsCharacters` | Opponent source only (`isOpponentAbility`) |
| Event breadth | wound/move/engage/heal/targeted/challenge/intervene | Narrow to printed verbs (+ intervene if needed) |
| Resolution | `performReaction` resolves inline (no pay) | Pay in `performReaction`; redirect in `EventRiskReactionTriggered` |
| Owner lookup | `getOwningCharacter` / `getOwningAttachment` | `getOwningCard` (the Risk) |

Reach for `Reaction_01014` (Vittoria — Thug-only redirect) or `Reaction_02016` when adapting attachment reactions; reach for `Reaction_03031` when porting that shape to a hand-paid Risk with effect-based wording.

References: `Reaction_03031` (Altruistic), `Reaction_02016` (structural template on attachments), `Reaction_01053` (Hexenjagd — "performer at that location" chooser semantics on a Risk).

### `EventCharacterIntervened` trigger semantics

Fired by `FrameworkActionsTrait::actHighDramaChallengeActionIntervene` after the intervener replaces the defender. Field semantics for `handleEvent` use:

- `$event->playerId` — the player who chose to intervene (the new defender's controller).
- `$event->oldTargetId` — the previously-targeted character (the original defender).
- `$event->newTargetId` — the **intervener** (the character that replaced the defender).

For a "When your performer intervenes" trigger, gate on `$event->playerId == $owner->ControllerId` (Risk's controller = the intervening player) plus `$intervener->hasTrait(...)` for any trait-prefixed gate. Threat is calculated *after* the intervention/refusal step resolves, so mutating `CHALLENGE_STAT` here lands before threat-calc reads it.

The event fires inside `actHighDramaChallengeActionIntervene` *before* `nextState("")`, so a `createReactionTransitionEvent` queued from your handler runs in the normal reaction-offer flow as the state machine processes pending events.

#### Intervene path ≠ `EventChallengeAccepted` (critical footgun)

`actHighDramaChallengeActionIntervene` sets `Game::CHALLENGE_ACCEPTED = true` and queues `EventCharacterIntervened` (+ usually `EventCardEngaged` for the intervener). It does **not** fire `EventChallengeAccepted`. That event is only emitted from `actHighDramaChallengeActionAccept` (plain accept with no intervention).

So printed text like **"After your performer's challenge is accepted, if their adversary intervened"** maps to **`EventCharacterIntervened` only**, gated on your challenger (`globals->get(Game::CHOSEN_PERFORMER)` controlled by the Risk owner + trait gate). Do **not** also listen for `EventChallengeAccepted` — that path is accept-without-intervene and must not fire the "adversary intervened" clause. Reference: `Reaction_03046b` (Passionate Pirate).

#### Event queue order on intervene

`actHighDramaChallengeActionIntervene` queues in order: `EventCharacterIntervened`, then (usually) `EventCardEngaged` for the intervener. A reaction transition queued from the Intervened handler therefore runs **after** the engage has applied. By offer/pay time the intervener is typically `Engaged == true` (exceptions: Odette Musketeer deferral, Rena weapon deferral). At `EventRiskReactionTriggered`, only engarde if still `Engaged`. Challengers are usually already engaged from `stIssueChallenge` / setup before intervene, so Pirate-style "engarde your challenger" can also gate `Engaged` at trigger time to avoid offering a no-op.

### "Trigger-named performer is the performer" — Reaction trait gates

When the printed text references a character by role ("When **your performer** intervenes," "When **your character** is wounded," etc.), that character IS the performer of the Reaction. Apply trait gates (`Strega`, `Sorcerer`, `Mercenary`, `Duelist`, `Pirate`, …) directly to the character named by the trigger event — do **not** search for a separate trait-bearing performer.

Compare:
- `Reaction_03010` (Manipulative) — "Strega Reaction" with no role-named performer in the trigger. The Reaction searches `getCharactersInPlayByPlayerId(owner->ControllerId)` for any Strega.
- `Reaction_03012` (Subtle) — "Sorcerer Strega Reaction: When **your performer** intervenes." The intervener (from `$event->newTargetId`) IS the performer; the Strega gate checks `$intervener->hasTrait("Strega")` directly. No separate search.
- `Reaction_03046a` (Passionate Duelist) — same intervene role as Subtle; gate `hasTrait("Duelist")` on `$event->newTargetId`, then engarde that character after pay.
- `Reaction_03046b` (Passionate Pirate) — "your performer" is the **challenger** (`CHOSEN_PERFORMER`), not the intervener. Gate `hasTrait("Pirate")` on the challenger; engarde the challenger after pay. Mutually exclusive with the Duelist clause on the same intervene event (you cannot be both intervening player and the challenger's controller for one challenge).
- `Reaction_03031` (Altruistic) — "Your **performer at that location** suffers those effects instead." Here "performer" means **another of your characters at the affected character's location** (`getCharactersAtLocationByPlayerId`, excluding the character being wounded/moved/engaged). The player picks which one via redirect buttons — same pool semantics as Hexenjagd's wound-performer chooser (`Reaction_01053`), not a search for a trait-bearing role elsewhere on the board.

This matters for `ISorcererAbility`'s `createSorcererAbilityStartEvent($performerId)` arg — pass the trigger-named character's id, not a generic "any Strega I control."

### Mutating `Game::CHALLENGE_STAT` mid-challenge

The active challenge stat is held in the `Game::CHALLENGE_STAT` global; threat is read from it later in `StatesTrait::stGenerateChallengeThreat`. Several Actions set it at challenge-issue time (e.g., `Action_03008` Arrogant → `STAT_COMBAT`); a Reaction can flip it mid-flow, between intervention/refusal and threat-calc.

Use `globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE)` (or other `STAT_*` constant) directly. Do **not** introduce a new `CHALLENGE_TYPE` constant unless intervention or refusal rules also differ — `CHALLENGE_TYPE` controls intervention/refusal gating, `CHALLENGE_STAT` controls the stat used in threat calc, and the two are orthogonal.

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

Combat-card cost discounts ("this card has -1 cost when …") live on the **Maneuver** via `getManeuverFromCombatCardDiscount`, not on the Risk class — the discount applies only when this card is being played as a combat-card maneuver. Gate on `$owner->Id == $combatCard->Id` so copied/other cards do not inherit it. Common predicates:

| Printed condition | Gate |
|---|---|
| "While the adversary is engaged" | `$adversary->Engaged` — `Maneuver_01084` |
| "If your participant has more [Finesse] than the adversary" | `$actor->ModifiedFinesse > $adversary->ModifiedFinesse` — `Maneuver_03036` |

Use Modified stats and parse the comparison literally (`>` vs `>=`). Push a translated explanation into `$explanations` when the discount applies.

### Pattern E.1 — Forced on the Risk class (no player choice)

`<b>Forced:</b>` abilities with no chooser belong on the Risk's `handleEvent`, not a separate ability class. There is no Forced base class and no sub-state. Common shape for duel-line Forced effects:

**"After your adversary is destroyed, if this card is in your dueling line • …"**

Gate chain (all required):

1. `EventCharacterDestroyed`
2. `$this->Location == Game::LOCATION_DUELING_LINE`
3. `$game->globals->get(Game::IN_DUEL)` is truthy
4. Destroyed character is the **adversary of this card's controller** — not merely "someone in the duel"

Participant / winner lookup (same shape as `_02052` Gutter Full of Roses, scoped to `$this->ControllerId`):

```php
$challengerId = $theah->getDuelChallengerId();
$defenderId = $theah->getDuelDefenderId();
$destroyedId = $event->characterId;

$participantId = null;
if ($destroyedId == $defenderId
    && $theah->getCharacterById($challengerId)->ControllerId == $this->ControllerId)
{
    $participantId = $challengerId;
}
elseif ($destroyedId == $challengerId
    && $theah->getCharacterById($defenderId)->ControllerId == $this->ControllerId)
{
    $participantId = $defenderId;
}
```

**WHY not use `getDuelRoundActor()` / `getDuelRoundOpponent()` here:** those are round-relative. At destroy time the current round's actor may not be the surviving participant you need. Challenger/defender ids are stable for the whole duel.

**Heal discipline** (when the Forced effect heals): only queue `createCharacterBeingHealedEvent` when the participant is not in discard/locker **and** `$participant->Wounds > 0`. Mirror `Maneuver_01052`. Notify before queueing.

**WHY on the Risk class:** Forced with no player input is a passive — `_01102` (Unfortunate: Forced equip from dueling line at end of round) is the same shape. Do not invent a `Forced_NNNNN` ability file.

References: `_03033` (Glorious — heal on adversary destroyed), `_01102` (Unfortunate — equip from dueling line), `_02052` (scheme Forced for "any player's adversary destroyed" — same challenger/defender lookup, different scope).

## State Wiring (`states.inc.php`)

For Pattern A City Actions, add a transition entry under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. Most Risk City Actions that issue challenges use the shared chooser:

```php
"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
```

If your Action transitions to a custom sub-state for a non-challenge effect, add a `States::HIGH_DRAMA_PLAYER_TURN_NNNNN` constant in `States.php` plus a state definition in `states.7s5s.php` (or a GameState class in `States/<expansion>/`). State ID convention: `4` + `CardNumber` zero-padded (e.g., `_03008` → `403008`). Don't engineer around hypothetical CD-card collisions. (Memory feedback.)

For Pattern C Maneuvers that transition to a sub-state (e.g., `Maneuver_01115`), add an entry under the duel's resolve-maneuver transition map and define the state. Mirror `Maneuver_01115`'s wiring.

For Pattern C.5 "you choose their combat card" hijacks, wire under **`DUEL_GAMBLE_REVEALED_EVENTS.transitions`** (after reveal), not resolve-maneuver. State id convention near the choose family: `5270NNNNN` (see `States::DUEL_CHOOSE_GAMBLE_CARD_03047`).

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
- **`IRiskThatTargetsCharacters`** — mark on the Risk class itself when any of its abilities targets a character (Actions/Reactions/Maneuvers that fire `EventCharacterTargeted` or use `IAbilityThatTargetsCharacters`). Applies equally for friendly-target choosers, not just enemy-target. Compare `_01083`, `_01115`, `_03008`, `_03011`, `_03034`.

## Cross-Cutting Helpers

- `$theah->getCharactersInCityByPlayerId(int $playerId): array` — characters of `playerId` currently at city locations.
- `$theah->getCharactersInPlayByPlayerId(int $playerId): array` — wider net: characters in city or home.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing characters at a location.
- `$theah->getCharactersAtLocation(string $location): array` — everyone at a location (defensive: filter by `isControlled()` and `ControllerId` when "opposing" is the intent).
- `$theah->cardInCity(Card $card): bool` — true when the card is at a city location.
- `$theah->getDuelRoundActor(): ?Character` / `getDuelRoundOpponent(): ?Character` — the round's participant + adversary.
- `$theah->getDuelChallengerId() / getDuelDefenderId() / getDuelOpponentId(int $actorId)` — id-only accessors. **All three return CHARACTER ids, not player ids.** Looking up a player from one of these requires `$theah->getCharacterById($id)->ControllerId`. Don't pass them to `getPlayerNameById($playerId)` — you'll print "0" or worse. The `challenger_id` / `defender_id` columns in the `duel` table are character primary keys (the dueling characters), not player primary keys.
- `Game::IN_DUEL` global — true between duel start and end.
- `Game::DUEL_GAMBLED` global — true after the actor locks in a combat card via gamble; cleared at end of round.
- `Game::CHOSEN_PERFORMER` / `CHOSEN_TARGET` / `CHALLENGE_TYPE` / `CHALLENGE_STAT` globals — set in `handleEvent` on `EventActionTriggered` to brief the challenge sub-state machine.
- `Game::EXTRA_ACTIONS` — integer counter read in `stNextPlayer`. When `> 0`, the current player takes another turn instead of advancing. Decremented each time `stNextPlayer` runs. Set by cards that grant "an extra action" (e.g., `Action_01090`, `Action_03013`). **Alone, this only keeps the same player — not the same performer.**
- `Game::EXTRA_ACTION_PERFORMER` — character id paired with `EXTRA_ACTIONS` when the follow-up action must be performed by a specific character and Pass is forbidden. Set alongside `EXTRA_ACTIONS = 1`; cleared when the turn actually ends (next player). Framework helpers on `Game.php` + enforcement in `ArgumentsTrait`, `FrameworkActionsTrait`, `Theah`. Pattern A.2 reference: `_03032`.
- `$character->canChallenge(): bool` — currently `return $this->isControlled();` only. It does **not** check Engaged. Layer `! $c->Engaged` yourself when the text imposes an engage cost.
- `$character->ModifiedInfluence` / `ModifiedFinesse` / `ModifiedCombat` / `ModifiedResolve` — live stats.
- `$this->getInjectCode(): string` — inline-styled card name for notifications.

Event factories you'll likely need:
- `createTransitionEvent($playerId, $sourceId, string $internalId, ?int $abilityId = null)` — move into a sub-state via the `*_EVENTS` transitions table.
- `createActionResolvedEvent($playerId, $actionId)` — fire when the Action's effect is fully resolved. NOT needed for challenge-issuing actions (the challenge resolution flow fires it).
- `createCardDrawnEvent($playerId, string $reason)` — draw one card.
- `createGainLethalEvent($actorId, Theah $theah)` — grant Lethal in a duel round.
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)` — move into the reaction's player-button state.
- `createCardEngagedEvent($playerId, $cardId, $sourceId = 0, $abilityId = "")` vs `createCardEngardedEvent($playerId, $cardId, $sourceId = 0, $abilityId = "")` — **NOT synonyms.** In this game's vocabulary `Engaged = true` means "committed / has acted"; `Engaged = false` means "en garde / ready". `createCardEngagedEvent` sets `Engaged = true`; `createCardEngardedEvent` clears it back to `false`. When the printed text uses **"en garde" as a verb** ("En garde target character"), you want `createCardEngardedEvent` (clears the flag); valid targets are characters whose `Engaged == true` (Action_01081's `isValidTargetForAbility` returns "Character is already En Garded" when `!Engaged`). When the text says "engage" you want `createCardEngagedEvent` and valid targets are `Engaged == false`. Read each one literally — they're opposite operations.

Targeted-batch deletion helpers (Pattern D.3 — see the producer side in `_01117`, `_01062`, `_01150` for the canonical "queue Moving + Add + Removed with shared batchId" idiom):
- `$theah->deleteRenownAddedToLocationEventsByBatchId(int $batchId)` / `deleteRenownRemovedFromLocationEventsByBatchId(int $batchId)` — pass-throughs to `DB` helpers that anchor on `'%EventRenown<X>%'` AND `'%batchId";i:{N};%'` (note trailing `;`). Prefer these over `deleteEventBatch($batchId)` when you want to cancel only the state-mutating add/remove events, not every batch member.

`queueEvent` vs `stackEvent` rule of thumb:
- `queueEvent` → priority = the event's own `priority` field (defaults to `MEDIUM_PRIORITY = 3`). The event runs after all currently-pending events with lower-priority numbers (higher actual priority).
- `stackEvent` → priority = `min(current event_priorities) - 1`. Pre-empts every currently-pending event.
- `getNextEvent` orders by `event_priority` ASC — **lower number dequeues first**.
- If you need your reaction transition / pay events / cancel handler to run *before* an existing high-priority batch in the queue, `stackEvent` every step. Mixing `queueEvent` and `stackEvent` is the standard footgun behind "my cancel doesn't cancel anything" — by the time `EventRiskReactionTriggered` fires, the high-priority events have already mutated state.

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
| `modules/php/cards/faf/_03012.php` (Subtle) | **Sorcerer Strega RiskReaction that mutates `Game::CHALLENGE_STAT` (Pattern D.2).** Triggers on `EventCharacterIntervened` with the intervener (`$event->newTargetId`) as the trigger-named performer; gates on intervener's `Strega` trait directly (no separate search). After pay, the `EventRiskReactionTriggered` handler emits `SorcererAbilityStart`, sets `CHALLENGE_STAT = STAT_INFLUENCE`, emits `SorcererAbilityPlayed`, and `setUsed`s. No `IRiskThatTargetsCharacters` (no character chooser). No new `CHALLENGE_TYPE` constant (intervention/refusal rules unchanged). |
| `modules/php/cards/faf/_03021.php` (Cornered) | **RiskCityAction that engages performer + issues Combat challenge to opposing Sorcerer/Monster, with side effects on refuse (engage them) and intervene (wound them).** Sets a fresh `CORNERED_CHALLENGE_TYPE` purely as a correlator — gates stay normal, but the Risk's `handleEvent` reads the global to disambiguate its own challenge from baseline ones when handling `EventChallengeRejected` and `EventCharacterIntervened`. Demonstrates the "side-effect-on-refuse/intervene → mint a CHALLENGE_TYPE" expansion, and the `! Engaged` performer-filter layered on `canChallenge()` for "Engage your performer" costs. |
| `modules/php/cards/faf/_03020.php` (Commanding) | **"Leader Action" target-and-move-Home + `ICancelReaction` RiskReaction that cancels a Renown movement (Pattern D.3).** The Action gates on `getLeaderByPlayerId` (no `RequiresPerformerSelected`), opposing-character chooser at the Leader's location, moves target to `LOCATION_PLAYER_HOME`. The Reaction triggers on `EventRenownMovingBetweenLocations` when the Leader sits at `$event->fromLocation`; `stackEvent`s the reaction transition + pay events; `implements ICancelReaction` so the post-pay `EventRiskReactionTriggered` is also stacked. The triggered handler calls the targeted helpers `deleteRenownAddedToLocationEventsByBatchId` + `deleteRenownRemovedFromLocationEventsByBatchId` (on `Theah` → `DB`) so the high-priority Add/Remove events are gone before they can fire. |
| `modules/php/cards/_7s5s/_01082.php` (A Heroic End) | **Pure-data Final Strike Maneuver baseline.** Track participant on `EventResolveManeuver`, react on `EventCharacterDestroyed` while `IN_DUEL` to mutate threat (`createThreatModifiedEvent` with +2 threat + Lethal for the surviving side). No state transition, no player choice. Reach for `_03022` if your Final Strike requires a chooser. |
| `modules/php/cards/faf/_03022.php` (Overzealous) | **Final Strike Maneuver with a post-death player choice (Pattern C.1).** En Garde a chosen character at the duel's location + conditional draw if participant was Zealot/Hunter. Captures `DuelLocation` on `EventResolveManeuver` (actor is in the locker by selection time); queues `createTransitionEvent` from `EventCharacterDestroyed` (not `EventResolveManeuver`); state is named `DUEL_END_OF_ROUND_03022` (52903022) and wired under `DUEL_END_OF_ROUND_EVENTS` (NOT `DUEL_RESOLVE_MANEUVER_EVENTS`); uses `createCardEngardedEvent` (the "en garde" verb makes characters NOT engaged); adds a Pass button with gate-on-pass that throws `UserException` when valid targets exist. |
| `modules/php/cards/_7s5s/_01135.php` + `Maneuver_01135` | **Pattern C.3 template — choice-at-activation Maneuver with one branch carrying a side effect.** "+2 Parry, or wound adversary + -2 Thrust to their next round." `EventManeuverActivated` `stackEvent`s a transition to `DUEL_RESOLVE_MANEUVER_01135`; `actFromManeuverWithId` records the choice into `$ReduceThrustNextRound` and queues the wound event for branch 2. Cross-round state (`$IsActive`, `$ReduceThrustNextRound`) reset on `EventManeuverCanceled`, `EventDuelEnd`, and (for the next-round-only modifier) `EventDuelEndOfRound`. |
| `modules/php/cards/faf/_03024.php` (Superstitious) | **Pattern C.3 pure-calc variant.** "Maneuver: If the adversary is a Sorcerer or Monster • +2 Parry or +2 Thrust." Adversary-trait gate via `getDuelRoundOpponent()->hasTrait('Sorcerer'\|'Monster')`. Stores choice in `$ChooseParry`; calc branches on it. No `EventResolveManeuver` handler — both branches are pure calc. |
| `modules/php/cards/faf/_03023.php` (Second Wind) | **Gambling Maneuver that suppresses end-of-round threat→wound conversion + carries threat forward (Pattern C.2).** City Action heals a wound on a 2+-wound performer; Maneuver intercepts `EventCharacterBeingWounded` by the unique `characterId == actor && sourceId == adversary` signature, zeroes `$event->wounds`, captures the amount into `PENDING_CHALLENGER_THREAT` / `PENDING_DEFENDER_THREAT` so `stDuelNewRound` seeds it onto the next round, and `DbQuery`s `duel_round.wounds_taken = 0` so the UI display and `duelParticipantWoundsTaken()` cross-round aggregate match reality. Tracks state via `$IsActive`; resets on `EventManeuverCanceled` and `EventDuelEndOfRound`. Lethality is not preserved (no `PENDING_*_THREAT_IS_LETHAL` global) — same limitation as `Maneuver_02039`. |
| `modules/php/cards/faf/_03031.php` (Altruistic) | **Effect-event redirect RiskReaction (Pattern D.4).** Intercepts opponent wound/move/engage on your character; player picks another of their characters at that location to suffer the effect instead. Clone-cancel-reemit from `Reaction_02016` with Risk pay deferred to `EventRiskReactionTriggered`. Trigger gates on `isOpponentAbility`, not `IAbilityThatTargetsCharacters`. `isValidTargetForAbility` only when the source ability implements that interface ("if they are able"); non-targeting abilities redirect unconditionally. No wound-on-redirect (unlike 02016). |
| `modules/php/cards/faf/_03032.php` (Bloody Entrance) | **Sorcerer City Action: wound performer + move to any location + mandatory extra action locked to same performer (Pattern A.2).** `RiskCityAction` + `ISorcererAbility`; Sorcerer-gated performers; destination pool = all city locations + Home (see `Action_03029` MOVE_FROM_PERFORMER helper). Sets `EXTRA_ACTIONS = 1` and `EXTRA_ACTION_PERFORMER = $performer->Id`. Pairs with `State_highDramaPhase03032` (location chooser, `"locationChosen"` transition — same JS trio as `03009`). Framework enforces locked performer + no Pass via `Game::EXTRA_ACTION_PERFORMER` helpers. |
| `modules/php/cards/faf/_03033.php` (Glorious) | **Forced on Risk class (Pattern E.1) + pure-resolve Gambling Maneuver.** Forced: `EventCharacterDestroyed` + `LOCATION_DUELING_LINE` + `IN_DUEL` + destroyed is controller's adversary → heal participant (only if wounded and still in play). Maneuver: `DUEL_GAMBLED` + `ModifiedInfluence >=` adversary + adversary not discarded/locker → wound on `EventResolveManeuver` (no calc). Demonstrates equal-or-greater (`>=`) vs `_03008`'s "more than" (`>`), and that Forced with no chooser stays on the Risk — no Forced ability file. |
| `modules/php/cards/faf/_03034.php` (La Voix des Sans Voix) | **Diplomat City Action: engage performer + En garde another controlled character at this location + may heal / else draw (Pattern A.3).** Diplomat + `!Engaged` performer gate; friendly same-location Engaged targets (`createCardEngardedEvent`); engage on `EventActionTriggered` before chooser; auto-draw when `Wounds == 0`; heal/draw second state uses `{id:1}`/`{id:2}` labeled buttons (not Pass). Pairs with `State_highDramaPhase03034` + `_2`. |
| `modules/php/cards/faf/_03035.php` (Loyal) | **Pressure +1 RiskReaction (Pattern D.2.1) + multi-step C.3 Maneuver.** Reaction: `EventPressureOccuring` + more non-Mercenaries than each opponent → after pay set `LOYAL_PRESSURE_TYPE` / `LOYAL_PLAYER_ID`; `pressureLocation()` adds +1 (any pressure type — do not reuse `PRESSURE_BONUS`). Maneuver: wound other controlled character at duel location • +1 Riposte or +2 Thrust. Two states (chooser then buttons); **`stackEvent` every intermediate transition** or pending `EventDuelCalculateManeuverValues` races ahead of the choice — do not re-emit calc. `IRiskThatTargetsCharacters` on Risk; `IAbilityThatTargetsCharacters` on Maneuver. |
| `modules/php/cards/faf/_03036.php` (Valroux Exemplar) | **Finesse cost discount + Duelist Pattern C.4 Maneuver.** `getManeuverFromCombatCardDiscount` when `ModifiedFinesse >` adversary. Maneuver: Duelist gate; `+1 Riposte` per other dueling-line card (`unset($cards[$owner->Id])`); if ≥3 other cards and adversary hand non-empty, `queueEvent` discard transition to adversary controller (`01115` JS hand-pick). Empty-hand skip at resolve (not `isAvailable`) so Riposte scaling stays offerable. No `IRiskThatTargetsCharacters` (hand discard, not character chooser). No sticky Maneuver state → `EventManeuverCanceled handler not needed`. |
| `modules/php/cards/faf/_03045.php` (Curious) | **Plain Action (Pattern B.1 claim-control) + Gambling Maneuver (wound participant + +2 Riposte).** Action: `RiskAction` (home performers eligible); wound performer then move to adjacent city location where `getControllerForLocation` is an opposing player (`!= 0`). Not `_03009`'s enemy/Mercenary content filter. No `IRiskThatTargetsCharacters` (location chooser). Maneuver: `DUEL_GAMBLED` only; calc `+2 Riposte`; resolve wounds `getDuelRoundActor()` — contrast `Maneuver_03033` (wounds adversary). Pairs with `State_highDramaPhase03045` (same JS location-chooser trio as `03009`/`03032`). |
| `modules/php/cards/faf/_03046.php` (Passionate) | **Two Pattern D.2 RiskReactions on `EventCharacterIntervened` that En Garde after pay.** `Reaction_03046a` (Duelist): you intervene → engarde intervener. `Reaction_03046b` (Pirate): your Pirate challenger + adversary intervened → engarde challenger. Critical: intervene sets `CHALLENGE_ACCEPTED` but never fires `EventChallengeAccepted` — Pirate listens to Intervened only. Split a/b (not one mode class). Engarde only if still `Engaged` at `EventRiskReactionTriggered`. No `IRiskThatTargetsCharacters` (fixed trigger targets). No new states/JS. |
| `modules/php/cards/faf/_03047.php` (Proper Drama) | **Scoundrel + Duelist Pattern C.5 Maneuvers.** `Maneuver_03047a`: `+1 Riposte`; arm choose-lock on resolve; on `EventDuelGambleCardsRevealed` for blocked adversary → waiting log + transition `"03047"` under `DUEL_GAMBLE_REVEALED_EVENTS` to owner; public `cards` via `getArgsFromManeuver` + `argsForState`; `actGambleCardChosen` must use actor deck + restore active player. `Maneuver_03047b`: cannot gamble next round (`eventCheck` `EventDuelAttemptGamble`, mirror `Technique_02037`; clear via owner ControllerId). Split a/b. Framework actor-deck fix is shared with normal gamble choose when active ≠ actor. |
| `modules/php/cards/reactions/ICancelReaction.php` | Marker interface — empty body. Implementing it changes `FrameworkActionsTrait::actChooseCardForReactionPaid` to `stackEvent` (not `queueEvent`) the post-pay `EventRiskReactionTriggered` and `EventRiskPlayed`. Required whenever your RiskReaction's effect needs to interleave ahead of `HIGH_PRIORITY` events still queued from the same trigger batch (e.g., Renown Add/Remove pairs). |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (City Action / Action / Maneuver / Reaction / Forced / Passive / A.2 / A.3 / B.1 claim-control vs content filter / C.3 multi-step / C.4 dueling-line count / C.5 next-round gamble / D.2.1 / D.2 intervene-engarde). Riposte/Parry/Thrust numbers go on the constructor and are not a "pattern." Parse "controlled by an opponent" (location) vs "enemy character", "Wound your participant" vs "Wound the adversary", "challenge accepted if adversary intervened" (→ `EventCharacterIntervened`, not `EventChallengeAccepted`), and "you choose their combat card" / "cannot gamble" (→ C.5, not mid-round AttemptGamble for the choose seat) before copying a mirror.
2. Confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's NNNNN, `WealthCost` is set, combat stats match the printed card (set `DashedX = true` for printed-dashed stats), all Traits exist in `TraitNames::$TraitsJson`.
3. Mark `implements IRiskThatTargetsCharacters` on the Risk class when any of its abilities targets a character. The interface marker lives on the Risk class itself, not the Action/Reaction/Maneuver. Skip it for location-chooser-only Actions (`_03009`, `_03032`, `_03045`), hand-discard choosers (`_03036`), and fixed-target Reactions with no character chooser (`_03012`, `_03046`).
4. Each Action/Maneuver/Reaction is its own file in the corresponding subdirectory (`actions/`, `maneuvers/`, `reactions/`). Create the subdirectory if the expansion doesn't have one yet.
5. For City Action challenges that route through the shared challenge target chooser, add `"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` to `states.inc.php`. For non-challenge sub-states, add a `States::HIGH_DRAMA_PLAYER_TURN_<NNNNN>` constant (`4<NNNNN>`) plus a state definition (or GameState class).
6. **Parse keyword(s) literally** before picking interfaces:
   - "Sorcerer …" → `implements ISorcererAbility` + emit start/played events.
   - "Strega …" / "Mercenary …" / "Diplomat …" / "Duelist …" / "Gambling …" → performer-trait or duel-state gate. NOT a Sorcerer ability.
   - Both can stack.
   - **"City Action:" vs "Action:"** — only the "City" prefix restricts performers to city characters. A plain "Action:" admits home performers too, even when the effect *implies* city movement. Don't pre-filter to `getCharactersInCityByPlayerId(...)`; start from `parent::getPerformersForAction(...)`.
7. **Use Modified stats** (`ModifiedInfluence`, `ModifiedFinesse`, …) for in-duel and in-city comparisons. Parse comparison wording literally: "more than" → `>`, "equal or greater" → `>=`, "equal or lower" → `<=`. Do not copy `_03008`'s strict `>` onto a card that says equal-or-greater (`_03033`).
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
13. **Side-effect-on-refuse/intervene → mint a CHALLENGE_TYPE.** Risk-class handlers reacting to `EventChallengeRejected` or `EventCharacterIntervened` cannot correlate via `$event->actionId` (no such field on either event). The challenger id is the *performer*, not stable. Set a fresh `<CARD>_CHALLENGE_TYPE` constant in `Game.php` and assign it in the Action's `EventActionTriggered` handler, then gate the Risk's handlers on `globals->get(CHALLENGE_TYPE) == <CARD>_CHALLENGE_TYPE`. Reference: `_03021` Cornered.
14. **`canChallenge()` is `return isControlled();` — it does NOT check `Engaged`.** If the printed text imposes an engage cost on the performer ("Engage your performer …"), add `&& ! $c->Engaged` (or `|| $p->Engaged → false`) in BOTH `isAvailableToPlayer` and `getPerformersForAction`. Reference: `Action_03021`.
15. **Final Strike with player choice (Pattern C.1):** capture `$DuelLocation = $participant->Location` on `EventResolveManeuver`, queue `createTransitionEvent` from `EventCharacterDestroyed` (not from resolve), wire the state under `DUEL_END_OF_ROUND_EVENTS` (state id `52901NNN`-style), name it `DUEL_END_OF_ROUND_NNNNN`. Route all post-death location queries through the stored `$DuelLocation` — `getDuelRoundActor()->Location` will be the locker by selection time. Reference: `_03022`.
16. **"En garde" the verb vs "engage" the verb — opposite operations.** `createCardEngardedEvent` sets `Engaged = false` (ready / en garde); `createCardEngagedEvent` sets `Engaged = true` (committed). When the printed text says "En garde target character," the valid targets are characters whose `Engaged == true` (you're putting them back into en garde). On RiskReactions, engarde after pay in `EventRiskReactionTriggered` (Pattern D.2) — see `_03046`. Reference: `Action_01081`, `Action_02051`, `Action_03034`, `Maneuver_03022`, `Reaction_03046a`/`b`.
17. **`getDuelChallengerId()` / `getDuelDefenderId()` / `getDuelOpponentId()` return CHARACTER ids, not player ids.** Resolve to a player via `$theah->getCharacterById($id)->ControllerId`. Passing them directly to `getPlayerNameById()` prints garbage.
18. **"Target if able" maneuvers get a Pass + gate.** Declare `actFromCardPass` as a `PossibleAction` on the GameState class, override `actFromManeuverPass` to `throw UserException` when valid targets exist, and add the alert-color Pass button in `OnUpdateActionButtons`. Without the gate, a player can silently skip a mandatory effect. Reference: `Maneuver_03022`.
19. **Choice-at-activation Maneuver (Pattern C.3):** when the player picks how the calc applies ("+X stat A or +X stat B"), `stackEvent` the `createTransitionEvent` from `EventManeuverActivated` — NOT `EventResolveManeuver`, which fires after calc and lands the prompt too late. State id `52500000 + NNNNN` (prefix `5250`), state name `duelResolveManeuver_NNNNN`, wired under `DUEL_RESOLVE_MANEUVER_EVENTS.transitions`. GameState class transitions table uses `"" => DUEL_RESOLVE_MANEUVER_EVENTS` and `actFromManeuverWithId` calls `$game->gamestate->nextState()` with no arg. Reset the choice field in `EventManeuverCanceled` (the Maneuver instance persists across rounds on `$theah->cards`). Pure-calc branches → no `EventResolveManeuver` handler; side-effect branches → queue the side-effect events directly from `actFromManeuverWithId`, not from a later resolve hook. **Multi-step (chooser then buttons):** `stackEvent` *every* intermediate transition until the calc-driving choice is recorded — `queueEvent` on step 2 races behind the still-pending `EventDuelCalculateManeuverValues` from `stResolveManeuverFromCombatCard`. Do **not** re-emit calc after the choice; fix ordering instead. Reference: `Maneuver_01135` (side-effect branch), `Maneuver_03024` (pure-calc), `Maneuver_03035` (multi-step wound cost + Riposte/Thrust).
20. **Threat→wound conversion suppression (Pattern C.2):** the conversion fires once, in `stDuelEndOfRound`, as a single `EventCharacterBeingWounded` whose signature is `characterId == actor.Id && sourceId == adversary.Id`. Suppress by zeroing `$event->wounds` on that match. Two non-obvious follow-ups:
    - **Cross-round carry-forward** uses `PENDING_CHALLENGER_THREAT` / `PENDING_DEFENDER_THREAT` (`stDuelNewRound` reads them onto the next round's starting pool). `ending_<actor>_threat` is wiped to 0 by SQL *before* the wound event is queued, so you cannot rely on the DB row preserving it.
    - **Also zero `duel_round.wounds_taken`** via direct `DbQuery` — the column was bumped during the round and feeds both the UI display and `Theah::duelParticipantWoundsTaken()` (used by `Maneuver_01107`). Without resetting it, downstream cards see wounds that never landed.
    - Lethality is not preserved across the rollover (no `PENDING_*_THREAT_IS_LETHAL` global). Add the global rather than special-casing the card if a future text requires it.
    - Reference: `Maneuver_03023` (Second Wind), `Maneuver_02039` (Add Threat — producer side of `PENDING_*_THREAT`).
21. **Effect-event redirect RiskReaction (Pattern D.4):** when text says opponent ability "would wound/move/engage" (not "targets"), gate on effect events + `isOpponentAbility` — do **not** require `IAbilityThatTargetsCharacters` at trigger time. Defer `releaseEvent` to `EventRiskReactionTriggered` after pay. Apply `isValidTargetForAbility` only when `loadAbility()` returns `IAbilityThatTargetsCharacters`; otherwise redirect unconditionally. "Performer at that location" = `getCharactersAtLocationByPlayerId` excluding the affected character. Structural template: `Reaction_02016` on attachments → `Reaction_03031` on Risks. Do not copy 02016's wound-on-redirect unless the card says so.
22. **Mandatory extra action locked to same performer (Pattern A.2):** when italic text says the follow-up action must be performed and the same character must be the performer, set **both** `Game::EXTRA_ACTIONS = 1` and `Game::EXTRA_ACTION_PERFORMER = $performer->Id` at effect resolution. Do **not** use `EXTRA_ACTIONS` alone — it only repeats the player's turn. Do **not** stash the lock in `CHOSEN_PERFORMER` — `stNextPlayer` deletes it. Framework enforcement is centralized; card code only sets the two globals. "Any location" destination pool = all `getCityLocations()` names + Home if not already there (not adjacent-only). Wound + move both need `eventCheck` before `queueEvent`. Reference: `_03032`, `Action_03029::getValidDestinationLocations`.
23. **Forced on the Risk class (Pattern E.1):** Forced with no player choice stays on `_NNNNN::handleEvent` — do not create a Forced ability file. For "after your adversary is destroyed, if this card is in your dueling line": gate on `EventCharacterDestroyed` + `LOCATION_DUELING_LINE` + `IN_DUEL`, then resolve the survivor via challenger/defender ids (not round actor/opponent). Heal only if participant is in play and `Wounds > 0`. Reference: `_03033`, `_01102`, `_02052` (lookup shape).
24. **Pure-resolve Maneuver (wound/draw/move only):** skip `EventDuelCalculateManeuverValues`; implement only `EventResolveManeuver`. Keep the `EventManeuverCanceled handler not needed` comment. Gate wound-adversary availability on `! characterIsInDiscardOrLocker($adversary)`. `eventCheck` before `queueEvent` for wounds. Reference: `Maneuver_03033`, `Maneuver_01055`.
25. **Diplomat (etc.) City Action engage + En garde friendly + may heal/draw (Pattern A.3):** trait-gate the performer; filter `!Engaged` for the engage cost; pay engage on `EventActionTriggered` before the target chooser when a chooser follows; En Garde targets = other controlled characters at the same location with `Engaged == true`; after engarde, if `Wounds == 0` auto-resolve the "if they do not" branch (draw), else a second state with labeled `{id:1}`/`{id:2}` buttons for heal vs draw — not Pass when the alternate is a positive effect. Reference: `_03034`, `Action_02051`, `Action_01049_2`.
26. **Pressure +1 RiskReaction (Pattern D.2.1):** trigger on `EventPressureOccuring`; apply after pay via a new binary `PRESSURE_TYPE` flag + player-id global; add +1 in `pressureLocation()` outside the per-stat loop (any pressure type). Do **not** reuse `PRESSURE_BONUS` (Pack Tactics / Influence-only). Clean up the player-id global with the other pressure globals. Reference: `Reaction_03035`, `_02044`, `Reaction_02019`.
27. **Wound-other-character cost + Riposte/Thrust choice (multi-step C.3):** gate availability on another controlled character at the duel location; state 1 = friendly chooser; **`stackEvent`** to state 2; state 2 = buttons + queue wound from `actFromManeuverWithId`; calc branches on stored flag. Mark `IRiskThatTargetsCharacters` on the Risk. Reference: `Maneuver_03035`.
28. **Dueling-line count ± conditional adversary discard (Pattern C.4):** count `LOCATION_DUELING_LINE` for the controller and `unset($cards[$owner->Id])` — the combat card is already in the line. Pure scaling → calc only (`Maneuver_01166`). Conditional discard on count ≥ N → `EventResolveManeuver`; skip transition if adversary hand empty (do **not** hide the whole maneuver via `isAvailable` when the calc is still useful). Hand discard does not need `IRiskThatTargetsCharacters`. Cost discount clauses ("this card has -1 cost when …") use `getManeuverFromCombatCardDiscount` on the Maneuver with Modified-stat / Engaged predicates. Reference: `Maneuver_03036`, `Maneuver_01166`, `Maneuver_01084`, `Maneuver_01115`.
29. **"Adjacent location controlled by an opponent" (Pattern B.1 claim-control):** filter with `getControllerForLocation($location) != 0 && != performer->ControllerId`. Do **not** copy `_03009`'s "enemy character OR available Mercenary" scan — claim control ≠ character presence. `includeHome = false`. If text also wounds the performer, queue wound then move on location confirm (`eventCheck` both). No `IRiskThatTargetsCharacters`. Reference: `Action_03045`, contrast `Action_03009`.
30. **"Wound your participant" on a Maneuver:** resolve wounds `getDuelRoundActor()`, not `getDuelRoundOpponent()`. Pair with calc for any printed Riposte/Parry/Thrust. Gambling with no further gate → `DUEL_GAMBLED` only. Reference: `Maneuver_03045`, `Maneuver_02018`; contrast `Maneuver_03033` (adversary).
31. **"Challenge accepted if adversary intervened" → `EventCharacterIntervened` only:** `actHighDramaChallengeActionIntervene` sets `CHALLENGE_ACCEPTED = true` but never fires `EventChallengeAccepted`. Gate on your challenger (`CHOSEN_PERFORMER` + trait) for the Pirate/challenger-side clause; gate on `$event->playerId` + intervener trait for the "you intervene" clause. Do not dual-listen to Accepted. Reference: `Reaction_03046b`, contrast `Reaction_03046a` / `Reaction_03012`.
32. **En garde after intervene (Pattern D.2):** defer `createCardEngardedEvent` to `EventRiskReactionTriggered` after pay. Intervene queues Engaged *after* Intervened, so by pay time the intervener is usually Engaged — still check `$character->Engaged` before engarde. Split distinct trait-prefixed Reactions into `a`/`b` files. No new states/JS when the framework reaction + pay flow is enough. Reference: `_03046`, Pattern D.2.
33. **Next-round "cannot gamble" (Pattern C.5):** arm on `EventResolveManeuver`; block with `eventCheck` on `EventDuelAttemptGamble` for the blocked adversary character id. Risk Maneuvers clear when the owner's **ControllerId** becomes the new round actor (`EventDuelNewRound`) — not an owning-character id (`Technique_02037` shape). Also clear on cancel / duel end. Reference: `Maneuver_03047b`, `Technique_02037`.
34. **"You choose their combat card if they gamble" (Pattern C.5):** arm on resolve; hijack on **`EventDuelGambleCardsRevealed`** (not AttemptGamble). Transition priority 8 runs after reaction transitions (6) so before-choose reactions still fire. Wire under `DUEL_GAMBLE_REVEALED_EVENTS`. Public `cards` via `getArgsFromManeuver` + State `argsForState` (client `args.args.args.cards`) — do not invent an ArgumentsTrait helper. `actGambleCardChosen` / stock choose args must use the **duel-round actor's** deck; `changeActivePlayer(actor)` before `useManeuver`/`noManeuver`. Waiting log notify before the transition. Reference: `Maneuver_03047a`, `State_duelChooseGambleCard_03047`, `Maneuver_01077`.
35. **Dual a/b Maneuvers:** same split discipline as dual Reactions (`_01108`, `_03047`). Distinct trait gates + distinct effects → separate classes, not one mode field.
