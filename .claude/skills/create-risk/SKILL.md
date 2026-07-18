---
name: create-risk
description: Implement or finish a Risk card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Risk). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Risk, or when they reference a card whose class extends Risk and has unimplemented Text. Triggers on phrases like "implement this risk", "finish _NNNNN" (when it extends Risk), "wire up the maneuver", "add the city action on this risk", or natural-language descriptions of a Risk card (faction-deck combat card with Riposte/Parry/Thrust, played as a maneuver during duels, sometimes carries a City Action / Action / Reaction).
---

# Creating a Risk

This skill covers cards that extend `Risk` — the faction-deck cards a player draws into hand and plays as combat cards during duels. They are **not** city-deck cards; each player's faction deck mixes Risks with Characters/Schemes/etc. A Risk has Riposte/Parry/Thrust combat stats and may carry one or more of: a Maneuver (duel-round modifier), a City Action (played from city), an Action (played from hand or in play), or a Reaction.


## How to use this skill (progressive disclosure)

1. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** — do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | City Action / A.1–A.6 |
| [pattern-b.md](pattern-b.md) | plain Action / B.1 |
| [pattern-c.md](pattern-c.md) | Maneuver / C.1–C.7 |
| [pattern-d.md](pattern-d.md) | Reaction / D.1–D.4 |
| [pattern-e.md](pattern-e.md) | Passive discounts / Forced (E.1) |
| [wiring.md](wiring.md) | states, JS hooks, pre-commit, style |
| [helpers.md](helpers.md) | Theah/Game helpers, event factories, queue vs stack |
| [references.md](references.md) | card id → what it demonstrates |
| [checklist.md](checklist.md) | full finish / regression checklist |

When in doubt, mirror a reference rather than invent.

> **Sibling skills:** `create-character`, `create-city-character`, `create-city-event-card`, `create-city-attachment`, `create-scheme`, `create-faction-attachment`. Maneuver/Technique mechanics and challenge-flow plumbing are largely shared with `create-character` — read its **Pattern E** (Techniques and Maneuvers) and the action-base table when wiring a Risk's challenge action.

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
- **`IRiskThatTargetsCharacters`:** mark on the Risk class itself (not its Actions/Maneuvers) when any of its abilities targets a character — **including when the target is your own character** (the interface tracks "this Risk hands the player a character chooser", not enemy-only targeting). Compare `_01083`, `_01115`, `_03008`, `_03011`, `_03034`, `_03056`, `_03057`, `_03058`. **Do not** mark it for location-chooser Actions (`_03009`, `_03032`, `_03045`) — those pick a destination string via `actFromCardWithLocations`, not a character. Performer selection via `RequiresPerformerSelected` alone is also not a "targets characters" chooser. Fixed-target Reactions that engarde/wound a character named by the trigger (`_03046`, `_03012`) also skip it — there is no chooser.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code.

| Card phrase | Pattern |
|---|---|
| **`<b>City Action:</b>`** | Pattern A — `RiskCityAction`. The Action lives in `cards/<expansion>/actions/Action_NNNNN.php`. Performer must be in the city (framework helper). |
| **`<b>Action:</b>`** (no "City") | Pattern B — `RiskAction`. Defaults to requiring the Risk in hand (`Card::Location == LOCATION_HAND`); override `overrideInHandCheck` only when the card text implies otherwise. **Performer pool is home + city** — do NOT filter to city characters even when the effect implies city (e.g. "move to an adjacent location"). The keyword "City" in the heading is mechanical: if absent, home performers are eligible too. |
| **`<b>Maneuver:</b>`** / **`<b>Duelist Maneuver:</b>`** / **`<b>Scoundrel Maneuver:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern C — `Maneuver` subclass in `cards/<expansion>/maneuvers/Maneuver_NNNNN.php`. Trait-prefixed Maneuvers add an `isAvailable` gate (`hasTrait` or `DUEL_GAMBLED`). |
| **`<b>Reaction:</b>`** | Pattern D — `RiskReaction`. Pre-commit hook requires hand-only guard (`Location == Game::LOCATION_HAND`) + `setUsed`/`isAvailable` literal calls. |
| **"When an opponent's ability would wound/move/engage your character"** (no "target" wording) | Pattern D.4 — effect-event redirect `RiskReaction`. Intercept `EventCharacterBeingWounded` / `EventCardMoving` / `EventCardEngaged` (± `EventCharacterIntervened` for duel intervention). Gate on opponent source, not `IAbilityThatTargetsCharacters`. See `Reaction_03031`. |
| **"While [adversary/condition] …"** / **"If your participant has more [Stat] … this card has -1 cost"** / **"If this card was gambled, it has -1 cost"** (combat-card cost or stat modifier) | Pattern E — cost discounts via `getManeuverFromCombatCardDiscount` on the Maneuver (`_01084`, `_03036`, `_03048`); other always-on effects may override `handleEvent` on the Risk class. |
| **`<b>Forced:</b>`** (no player choice; fires automatically) | Pattern E.1 — `handleEvent` on the **Risk class itself**, not a separate Action/Reaction/Maneuver file. Common gates: `Location == LOCATION_DUELING_LINE`, `IN_DUEL`, destroyed character is your adversary. See `_03033` (Glorious), `_01102` (Unfortunate). |
| **`<b>Sorcerer …:</b>`** | The ability class (Action/Reaction/Maneuver) additionally `implements ISorcererAbility` — must emit `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). |
| **`<b>Strega …:</b>`** / **`<b>Mercenary …:</b>`** / **`<b>Diplomat …:</b>`** / **`<b>Duelist …:</b>`** | **Mechanical performer-trait gates**, NOT Sorcerer abilities. Enforce via `hasTrait("Diplomat")` (etc.) on the chosen performer or `getDuelRoundActor()`. Do NOT `implement ISorcererAbility` for these. Can stack with Sorcerer ("Sorcerer Strega Reaction" is both). |
| **`<b>Leader …:</b>`** | **Leader is the performer**, by mechanical restriction. Each player has at most one Leader (`getLeaderByPlayerId($playerId)`) — fetch it directly. Do **not** set `RequiresPerformerSelected = true`; there's no choice to make. For "Leader Action", `isValidTargetForAbility` resolves the Leader via the Risk's `ControllerId` instead of reading `CHOSEN_PERFORMER`. Mirror `Action_01024` (Bravos), `Action_03020` (Commanding). "Leader Reaction:" follows the same shape — the gate is "player owns a Leader at the printed location reference." |
| **"… then they may perform another action"** with *(It must be performed and they must be the performer of the action)* | Pattern A.2 — grant `EXTRA_ACTIONS = 1` **and** set `EXTRA_ACTION_PERFORMER` to the wound/move performer's id. Framework enforces same character + no Pass. See `_03032`. Do **not** rely on `EXTRA_ACTIONS` alone — it only keeps the same *player* on turn. |
| **"Engage your performer • En garde another character you control … Then, that character may heal … If they do not, draw"** | Pattern A.3 — Diplomat (or other trait) City Action: engage cost + same-location friendly Engaged target + may-heal / else-draw branch. See `_03034`. |
| **"Target an opposing character • If their controller does not control this location, they claim it and you move a Renown …"** | Pattern A.4 — opponent claims (via target) + you move Renown. Parse **they** vs **you** literally — do not rewrite into you-claim. See `_03056`. |
| **"Engage your performer • Issue an [Influence/Combat/Finesse] challenge … If the challenge is refused, claim …"** (no *may*) | Pattern A.5 — engage cost + stat challenge via shared chooser + auto-claim on refuse via fresh `CHALLENGE_TYPE` correlator (also keeps type off `stIssueChallenge` auto-engage). Influence → filter `!DashedInfluence`. Do **not** gate availability on claimability; do **not** use a Reaction for mandatory claim (`Reaction_03005` is optional). See `_03057`. |
| **"Duelist City Action: Target an opposing character • If their controller has more characters at this location than you, your performer issues a [Combat] challenge"** | Pattern A.6 — trait-gate performer + location headcount `>` as target filter + `NORMAL_CHALLENGE_TYPE` Combat via shared chooser. Bullet-**If** = availability filter (same discipline as A.4), not a post-target branch. No custom `CHALLENGE_TYPE` when there is no refuse/intervene side effect. See `_03058`. |
| **"+X[Parry] and +Y[Thrust] for each opposing character"** (± Gambling; location often omitted in print) | Pattern C.7 — count opposing at **duel actor location** (not Ren-style global in-play). Pure calc. See `Maneuver_03058`. |
| **"When a pressure occurs … Add +1 to your total for the pressure"** | Pattern D.2.1 — RiskReaction on `EventPressureOccuring`; mint a new `PRESSURE_TYPE` binary flag + player-id global; apply in `pressureLocation()`. Do **not** reuse `PRESSURE_BONUS` (Pack Tactics / Influence-only). See `_03035`. |
| **"Wound your other character at this location • +X [stat A] or +Y [stat B]"** | Pattern C.3 multi-step — character chooser state, then choice buttons; `stackEvent` **every** step until the calc-driving choice is recorded. See `_03035`. |
| **"+X [stat] for each other card in your dueling line"** (± **"If you have N or more other cards … adversary discards"**) | Pattern C.4 — count other cards at `LOCATION_DUELING_LINE` for the controller in calc; optional resolve-time discard transition when count ≥ N. See `_03036`, `Maneuver_01166`. |
| **"If your participant has more [Stat] than the adversary, this card has -1 cost"** | Pattern E via Maneuver — `getManeuverFromCombatCardDiscount` with Modified-stat comparison (not a separate Passive file). See `_03036`, `_01084`. |
| **"If this card was gambled, it has -1 cost"** | Pattern E via Maneuver — `getManeuverFromCombatCardDiscount` gated on `Game::DUEL_GAMBLED` (set in `actChooseGambleCard` before combat-card pay). See `Maneuver_03048`. |
| **"Move all threat from your participant to the adversary"** / **"Remove all threat from [participant]"** | Pattern C.6 — pure calc: Riposte (move) or Parry (remove) += `getCurrentDuelThreat(actor)`. See `Maneuver_03048`, `Technique_02012`. |
| **"Move … to an adjacent location controlled by an opponent"** / **"claimed by an opponent"** | Pattern B.1 claim-control filter — `getControllerForLocation($location) != 0 && != performer->ControllerId`. Not the same as "enemy character at location" (`_03009`). See `_03045`. |
| **"Wound your participant • +X [stat]"** (Gambling / other Maneuver) | Pattern C — calc branch for the stat; resolve wounds `getDuelRoundActor()` (your participant), not the adversary. See `Maneuver_03045`, `Maneuver_02018`. |
| **"After your performer intervenes • En garde them"** / **"… challenge is accepted, if their adversary intervened • En garde your performer"** | Pattern D.2 on `EventCharacterIntervened` — engarde deferred to `EventRiskReactionTriggered`. Trait gate on the trigger-named performer. See `_03046`. |
| **Two distinct trait-prefixed Reactions on one Risk** | Split into `Reaction_NNNNNa` / `Reaction_NNNNNb` (mirror `_03027` / `_03016`). Do not merge into one class with a mode field. |
| **"The adversary cannot gamble during their next round"** | Pattern C.5 — arm on resolve; `eventCheck` `EventDuelAttemptGamble` for blocked adversary character. Mirror `Technique_02037`; clear via owner **ControllerId** on Risk Maneuvers. See `Maneuver_03047b`. |
| **"If the adversary gambles during their next round, you choose their combat card"** (± **+X [stat]**) | Pattern C.5 — arm on resolve; hijack on `EventDuelGambleCardsRevealed` (not AttemptGamble); transition to Maneuver owner; public choose state; `actGambleCardChosen` uses actor deck. See `Maneuver_03047a`. |
| **Two distinct trait-prefixed Maneuvers on one Risk** | Split into `Maneuver_NNNNNa` / `Maneuver_NNNNNb` (mirror `_01108`). Same discipline as dual Reactions. |

A single Risk freely combines these. `_01115` has both a City Action and a Maneuver. `_03008` has both a City Action and a Gambling Maneuver. `_03033` has both a Forced (on the Risk class) and a Gambling Maneuver. `_03034` is a single Diplomat City Action (Pattern A.3). `_03056` is a single City Action (Pattern A.4 opponent-claim + Renown move). `_03057` is a single City Action (Pattern A.5 engage + Influence challenge + auto-claim on refuse). `_03058` composes Pattern A.6 (Duelist outnumbered Combat challenge) with Pattern C.7 (Gambling +Parry/+Thrust per opposing at duel location). `_03035` has both a pressure Reaction and a multi-step C.3 Maneuver. `_03036` composes a Finesse cost discount with a Duelist C.4 Maneuver (line-count Riposte + conditional discard). `_03045` has a plain Action (claim-control move) and a Gambling Maneuver (wound participant + Riposte). `_03046` has two Pattern D.2 intervene/engarde Reactions (Duelist + Pirate). `_03047` has dual a/b Maneuvers (Scoundrel choose-gamble + Duelist cannot-gamble). `_03048` composes a gambled cost discount with a Scoundrel C.6 move-all-threat Maneuver. `_01083` is a single City Action only.

## Finish (short)

1. Walk each printed Text clause → exactly one pattern (see shape table). Parse literal wording traps (they/you claim, wound participant vs adversary, intervene ≠ ChallengeAccepted, mandatory claim-on-refuse ≠ optional Reaction, bullet-If headcount as target filter not post-branch, "for each opposing character" = duel location not global in-play, etc.) before copying a mirror.
2. Constructor: `initializeFaction`, matching `CardNumber`, `WealthCost`, combat stats / `DashedX`, Traits in `TraitNames::$TraitsJson`.
3. Ability files in `actions/` / `maneuvers/` / `reactions/`; mark `IRiskThatTargetsCharacters` on the Risk when any ability uses a character chooser (not location-only / fixed-target / hand-discard).
4. Wire states + JS when you add card-specific sub-states — see [wiring.md](wiring.md).
5. Satisfy pre-commit literals (ActionResolved / ManeuverCanceled / hand `==` / setUsed / Sorcerer start+played).
6. `php -l` on touched PHP.

**Deep checklist (hooks, challenge correlators, C.1–C.6 / D.* footguns):** [checklist.md](checklist.md)
