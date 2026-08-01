---
name: create-risk
description: Implement or finish a Risk card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Risk). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Risk, or when they reference a card whose class extends Risk and has unimplemented Text. Triggers on phrases like "implement this risk", "finish _NNNNN" (when it extends Risk), "wire up the maneuver", "add the city action on this risk", "adversary has more wounds … -1 cost", "two Duelist Maneuvers", "equip this card to target opposing", "text box as blank", "Fate's Silence", or natural-language descriptions of a Risk card (faction-deck combat card with Riposte/Parry/Thrust, played as a maneuver during duels, sometimes carries a City Action / Action / Reaction).
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
| [pattern-a.md](pattern-a.md) | City Action / A.1–A.10 |
| [pattern-b.md](pattern-b.md) | plain Action / B.1 / B.2 RiskAttachment equip |
| [pattern-c.md](pattern-c.md) | Maneuver / C.1–C.9 |
| [pattern-d.md](pattern-d.md) | Reaction / D.1–D.4 |
| [pattern-e.md](pattern-e.md) | Passive discounts / Forced (E.1) / Action-only Leader discount (E.2) / text-box blank (E.3) |
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
- **`IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters`:** mark when the printed text uses **"Target"** / **"target"** (or similar Rules-Team wording) and the ability hands the player a character chooser — **including friendly choosers**. Compare `_01083`, `_01115`, `_03008`, `_03011`, `_03034`, `_03056`, `_03057`, `_03058`. The Action/Maneuver/Reaction implements `IAbilityThatTargetsCharacters` (`isValidTargetForAbility`); the Risk gets `IRiskThatTargetsCharacters`. **Do not** mark either for: location-chooser Actions (`_03009`, `_03032`, `_03045`); hand-discard choosers; fixed-target Reactions (`_03046`, `_03012`); or choosers whose text never says "target" (e.g. `_03060` "Heal … from another character", `_03068` "They must move an en garde character", `_03069` "swap … with your other character", `_03071` "Engage an opposing character" — still a UI chooser, but Cesca `Reaction_01008` must not copy). Performer selection via `RequiresPerformerSelected` alone is also not a "targets characters" chooser.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code.

| Card phrase | Pattern |
|---|---|
| **`<b>City Action:</b>`** | Pattern A — `RiskCityAction`. The Action lives in `cards/<expansion>/actions/Action_NNNNN.php`. Performer must be in the city (framework helper). |
| **`<b>Action:</b>`** (no "City") | Pattern B — `RiskAction`. Defaults to requiring the Risk in hand (`Card::Location == LOCATION_HAND`); override `overrideInHandCheck` only when the card text implies otherwise. **Performer pool is home + city** — do NOT filter to city characters even when the effect implies city (e.g. "move to an adjacent location"). The keyword "City" in the heading is mechanical: if absent, home performers are eligible too. |
| **`<b>Maneuver:</b>`** / **`<b>Duelist Maneuver:</b>`** / **`<b>Scoundrel Maneuver:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern C — `Maneuver` subclass in `cards/<expansion>/maneuvers/Maneuver_NNNNN.php`. Trait-prefixed Maneuvers add an `isAvailable` gate (`hasTrait` or `DUEL_GAMBLED`). |
| **`<b>Reaction:</b>`** | Pattern D — `RiskReaction`. Pre-commit hook requires hand-only guard (`Location == Game::LOCATION_HAND`) + `setUsed`/`isAvailable` literal calls. |
| **`<b>City Reaction:</b>`** (on a Risk) | Pattern D — still `RiskReaction` from hand (Risk is the cost). **Additionally** gate offer on `getCharactersInCityByPlayerId(owner->ControllerId)` non-empty (mirror scheme City Reactions / `Reaction_02053`). Do **not** invent a `RiskCityReaction` base. See `_03068`. |
| **"After an opponent passes • They must move an en garde character from their Home to a City location"** | Pattern D.1.1 — pass-trigger + mandatory opponent Home→City move via reaction-button stages (no GameStates). See `_03068`. |
| **"When an opponent's ability would wound/move/engage your character"** (no "target" wording) | Pattern D.4 — effect-event redirect `RiskReaction`. Intercept `EventCharacterBeingWounded` / `EventCardMoving` / `EventCardEngaged` (± `EventCharacterIntervened` for duel intervention). Gate on opponent source, not `IAbilityThatTargetsCharacters`. See `Reaction_03031`. |
| **"While [adversary/condition] …"** / **"If your participant has more [Stat] … this card has -1 cost"** / **"While the adversary has more wounds than your participant, this card has -1 cost"** / **"If this card was gambled, it has -1 cost"** (combat-card cost or stat modifier) | Pattern E — combat-card discounts via `getManeuverFromCombatCardDiscount` on the Maneuver (`_01084`, `_03036`, `_03048`, `_04007`); other always-on effects may override `handleEvent` on the Risk class. **Wounds** use `$adversary->Wounds > $actor->Wounds` (not Modified stats). On dual-Maneuver Risks, put the discount on **one** Maneuver only — `Card` sums every Maneuver (`_04007`). |
| **"This card has -1 cost if your Leader is a [Trait] …"** / **"While your Leader is a [Trait], this card has -1 cost"** (Action/City Action present; **no** Maneuver printed) | Pattern E.2 — `getActionFromHandDiscount` on the Action (`_01159`, `_01160`, `_03071`). Do **not** invent a discount-only Maneuver; combat-card pay stays full WealthCost. |
| **`<b>Forced:</b>`** (no player choice; fires automatically) | Pattern E.1 — `handleEvent` on the **Risk class itself**, not a separate Action/Reaction/Maneuver file. Common gates: `Location == LOCATION_DUELING_LINE`, `IN_DUEL`, destroyed character is your adversary. See `_03033` (Glorious), `_01102` (Unfortunate). **Exception:** Forced that only applies **while the Risk is equipped as a RiskAttachment** lives on the FakeAttachment — see Pattern B.2 / `_01025_Burden` / `_04008_Silence`. |
| **"The equipped character treats their text box as blank"** / **"(They cannot use any of their abilities)"** | Pattern E.3 — stamp `Game::FATES_SILENCE_CONDITION` on equip; Theah skips polymorphic Character `handleEvent`/`eventCheck` (core only); gate Character-owned Action/Maneuver/Technique availability. Attachment-owned abilities keep working. See `_04008_Silence`. |
| **`<b>Sorcerer …:</b>`** | The ability class (Action/Reaction/Maneuver) additionally `implements ISorcererAbility` — must emit `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). |
| **`<b>Strega …:</b>`** / **`<b>Mercenary …:</b>`** / **`<b>Diplomat …:</b>`** / **`<b>Duelist …:</b>`** | **Mechanical performer-trait gates**, NOT Sorcerer abilities. Enforce via `hasTrait("Diplomat")` (etc.) on the chosen performer or `getDuelRoundActor()`. Do NOT `implement ISorcererAbility` for these. Can stack with Sorcerer ("Sorcerer Strega Reaction" is both). |
| **`<b>Leader …:</b>`** | **Leader is the performer**, by mechanical restriction. Each player has at most one Leader (`getLeaderByPlayerId($playerId)`) — fetch it directly. Do **not** set `RequiresPerformerSelected = true`; there's no choice to make. For "Leader Action", `isValidTargetForAbility` resolves the Leader via the Risk's `ControllerId` instead of reading `CHOSEN_PERFORMER`. When a later pressure/challenge pipeline needs `CHOSEN_PERFORMER`, **set it yourself** to the Leader id on announce. Mirror `Action_01024` (Bravos), `Action_03020` (Commanding), `Action_03067` (Ambitious — Leader City Action + pressure). "Leader Reaction:" follows the same shape — the gate is "player owns a Leader at the printed location reference." |
| **"Leader City Action: Wound your performer • If you control fewer locations than an opponent, pressure … with your choice of [Combat], [Finesse], or [Influence]. If successful, claim … Send this card to The Locker"** | Pattern A.8 — Leader + wound cost + location-count If + choose-stat pressure + claim on success + locker. See `_03067`. |
| **"City Action: If this location is controlled by an opponent • Engage an opposing character"** (no "Target") | Pattern A.9 — opponent claim-control If as performer filter + engage non-engaged opposing at location; private `isValidEngageCharacter` (no Cesca interfaces). See `_03071`. |
| **"Destroy all engaged attachments equipped to target opposing character. Then, engage each of their equipped attachments"** | Pattern A.10 — Target Cesca interfaces + snapshot engaged→unequip/discard then unengaged→`createCardEngagedEvent`; skip FakeAttachment. See `_03072`. |
| **"Maneuver: Destroy all engaged attachments equipped to the adversary"** | Pattern C pure-resolve — snapshot engaged non-Fake → unequip+discard; no chooser. See `Maneuver_03072` (contrast choose-one `Technique_02026b`). |
| **"… then they may perform another action"** with *(It must be performed and they must be the performer of the action)* | Pattern A.2 — grant `EXTRA_ACTIONS = 1` **and** set `EXTRA_ACTION_PERFORMER` to the wound/move performer's id. Framework enforces same character + no Pass. See `_03032`. Do **not** rely on `EXTRA_ACTIONS` alone — it only keeps the same *player* on turn. |
| **"Engage your performer • En garde another character you control … Then, that character may heal … If they do not, draw"** | Pattern A.3 — Diplomat (or other trait) City Action: engage cost + same-location friendly Engaged target + may-heal / else-draw branch. See `_03034`. |
| **"Target an opposing character • If their controller does not control this location, they claim it and you move a Renown …"** | Pattern A.4 — opponent claims (via target) + you move Renown. Parse **they** vs **you** literally — do not rewrite into you-claim. See `_03056`. |
| **"Engage your performer • Issue an [Influence/Combat/Finesse] challenge … If the challenge is refused, claim …"** (no *may*) | Pattern A.5 — engage cost + stat challenge via shared chooser + auto-claim on refuse via fresh `CHALLENGE_TYPE` correlator (also keeps type off `stIssueChallenge` auto-engage). Influence → filter `!DashedInfluence`. Do **not** gate availability on claimability; do **not** use a Reaction for mandatory claim (`Reaction_03005` is optional). See `_03057`. |
| **"Duelist City Action: Target an opposing character • If their controller has more characters at this location than you, your performer issues a [Combat] challenge"** | Pattern A.6 — trait-gate performer + location headcount `>` as target filter + `NORMAL_CHALLENGE_TYPE` Combat via shared chooser. Bullet-**If** = availability filter (same discipline as A.4), not a post-target branch. No custom `CHALLENGE_TYPE` when there is no refuse/intervene side effect. See `_03058`. |
| **"You may engage your performer, if you do, ignore all costs • …"** | Pattern A.7 — `$WillEngage` on Risk + pre-pay engage GameState (`stackEvent` on `EventEnteringPayState`, wire under `HIGH_DRAMA_IN_HAND_ACTION_EVENTS`) + `getActionFromHandDiscount` += WealthCost. Legacy `_01133` still uses a pay-state Reaction. Do **not** engage-at-announce as a mandatory cost. |
| **"+X[Parry] and +Y[Thrust] for each opposing character"** (± Gambling; location often omitted in print) | Pattern C.7 — count opposing at **duel actor location** (not Ren-style global in-play). Pure calc. See `Maneuver_03058`. |
| **"Look at the top N of your adversary's deck. Reveal one and add its [Parry] or [Thrust] … Replace them … If [participant trait], sink any …"** | Pattern C.8 — C.3 timing (peek+stat before calc) + private look / public reveal / unchosen sink+reorder. Revealed pick is **excluded** from sink and reorder pools. See `Maneuver_03059`. |
| **"Swap your participant with your other character at this location"** (± Gambling **+X [Riposte]**) | Pattern C.9 — mid-duel participant replace via `swapParticipantsInDuel`; friendly same-location chooser; Harpoon activate+confirm guards (not Lodestone/Shackles). See `_03069`. |
| **"When a pressure occurs … Add +1 to your total for the pressure"** | Pattern D.2.1 — RiskReaction on `EventPressureOccuring`; mint a new `PRESSURE_TYPE` binary flag + player-id global; apply in `pressureLocation()`. Do **not** reuse `PRESSURE_BONUS` (Pack Tactics / Influence-only). See `_03035`. |
| **"Wound your other character at this location • +X [stat A] or +Y [stat B]"** | Pattern C.3 multi-step — character chooser state, then choice buttons; `stackEvent` **every** step until the calc-driving choice is recorded. See `_03035`. |
| **"+X [stat] for each other card in your dueling line"** (± **"If you have N or more other cards … adversary discards"**) | Pattern C.4 — count other cards at `LOCATION_DUELING_LINE` for the controller in calc; optional resolve-time discard transition when count ≥ N. See `_03036`, `Maneuver_01166`. |
| **"If your participant has more [Stat] than the adversary, this card has -1 cost"** | Pattern E via Maneuver — `getManeuverFromCombatCardDiscount` with Modified-stat comparison (not a separate Passive file). See `_03036`, `_01084`. |
| **"While the adversary has more wounds than your participant, this card has -1 cost"** | Pattern E via Maneuver — `getManeuverFromCombatCardDiscount` with `$adversary->Wounds > $actor->Wounds` (not Modified Combat/Finesse). See `_04007`. |
| **"If this card was gambled, it has -1 cost"** | Pattern E via Maneuver — `getManeuverFromCombatCardDiscount` gated on `Game::DUEL_GAMBLED` (set in `actChooseGambleCard` before combat-card pay). See `Maneuver_03048`. |
| **"This card has -1 cost if your Leader is a Villain or Pirate"** / **"While your Leader is a Villain/Hero/Diplomat, … -1 cost"** (with Action only) | Pattern E.2 — `getActionFromHandDiscount` on the Action; null-check Leader; Id-gate the Action. See `_03071`, `_01160`, `_01159`. |
| **"Move all threat from your participant to the adversary"** / **"Remove all threat from [participant]"** / **"Discard threat … in excess of your adversary's [duel] stat"** | Pattern C.6 — pure calc: Riposte (move all) or Parry (remove all / discard excess). Excess = `max(0, threat − adversary Modified CHALLENGE_STAT)` — same `match` as Restricted Hostilities. See `Maneuver_03048`, `Technique_02012`, `Maneuver_03070`. |
| **"Move … to an adjacent location controlled by an opponent"** / **"claimed by an opponent"** | Pattern B.1 claim-control filter — `getControllerForLocation($location) != 0 && != performer->ControllerId`. Not the same as "enemy character at location" (`_03009`). See `_03045`. |
| **"Equip this card to [an opposing character / target opposing …]"** (± **"This ability cannot be copied"**; ± while-equipped Forced / blank text box) | Pattern B.2 — `RiskAction` + `createRiskAttachment` → FakeAttachment `_NNNNN_Suffix` (`IRiskAttachment`). Forced "if equipped • Destroy" lives on the **attachment**, not Risk E.1. Printed **"target"** → Cesca interfaces; **"cannot be copied"** → do **not** add to Cesca `Reaction_01008` allow-list. Blank text box → Pattern E.3. See `_01025`, `_04008`, `_01161`. |
| **`<b>Forced:</b> At the end of High Drama, if this card is equipped • Destroy it`** | Pattern B.2 attachment Forced — `EventHighDramaPhaseEnd` + `isAttached()` → `removeRiskAttachment` on the FakeAttachment (`_01025_Burden`, `_04008_Silence`). **Not** E.1 on the Risk class (Risk is in `LOCATION_PERMANENTLY_HIDDEN` while equipped). |
| **"Wound your participant • +X [stat]"** (Gambling / other Maneuver) | Pattern C — calc branch for the stat; resolve wounds `getDuelRoundActor()` (your participant), not the adversary. See `Maneuver_03045`, `Maneuver_02018`. |
| **"After your performer intervenes • En garde them"** / **"… challenge is accepted, if their adversary intervened • En garde your performer"** | Pattern D.2 on `EventCharacterIntervened` — engarde deferred to `EventRiskReactionTriggered`. Trait gate on the trigger-named performer. See `_03046`. |
| **Two distinct trait-prefixed Reactions on one Risk** | Split into `Reaction_NNNNNa` / `Reaction_NNNNNb` (mirror `_03027` / `_03016`). Do not merge into one class with a mode field. |
| **"The adversary cannot gamble during their next round"** | Pattern C.5 — arm on resolve; `eventCheck` `EventDuelAttemptGamble` for blocked adversary character. Mirror `Technique_02037`; clear via owner **ControllerId** on Risk Maneuvers. See `Maneuver_03047b`. |
| **"If the adversary gambles during their next round, you choose their combat card"** (± **+X [stat]**) | Pattern C.5 — arm on resolve; hijack on `EventDuelGambleCardsRevealed` (not AttemptGamble); transition to Maneuver owner; public choose state; `actGambleCardChosen` uses actor deck. See `Maneuver_03047a`. |
| **Two distinct trait-prefixed Maneuvers on one Risk** | Split into `Maneuver_NNNNNa` / `Maneuver_NNNNNb` (mirror `_01108`). Same discipline as dual Reactions — including **two Duelist** (same trait, different effects: `_04007`). When the Gambling half only adds calc on top of the same resolve effect, `b extends a` is fine (`_03069`). Card-level "-1 cost while …" → discount on **exactly one** of a/b (`Card` sums Maneuvers — `_04007`). |

A single Risk freely combines these. `_01115` has both a City Action and a Maneuver. `_03008` has both a City Action and a Gambling Maneuver. `_03033` has both a Forced (on the Risk class) and a Gambling Maneuver. `_03034` is a single Diplomat City Action (Pattern A.3). `_03056` is a single City Action (Pattern A.4 opponent-claim + Renown move). `_03057` is a single City Action (Pattern A.5 engage + Influence challenge + auto-claim on refuse). `_03058` composes Pattern A.6 (Duelist outnumbered Combat challenge) with Pattern C.7 (Gambling +Parry/+Thrust per opposing at duel location). `_03059` is a single Pattern C.8 Maneuver (adversary-deck peek → reveal for Parry/Thrust → unchosen sink/reorder). `_03060` composes Pattern A.7 (may engage / ignore costs + heal another) with a Gambling pure-resolve heal Maneuver. `_03067` is a single Pattern A.8 Leader City Action (wound + location-count If + pressure + claim + locker). `_03068` is a single Pattern D.1.1 City Reaction (pass → opponent must move en garde Home→City). `_03069` is dual Pattern C.9 (plain swap + Gambling +1 Riposte swap). `_03070` is a single Pattern C.6 excess-threat Parry Maneuver (Comforting). `_03071` composes Pattern E.2 (Leader Villain/Pirate Action discount) with Pattern A.9 (opponent-controlled location → engage opposing). `_03072` composes Pattern A.10 (Target destroy-all engaged attachments + engage remaining) with a pure-resolve Maneuver (destroy all engaged on adversary). `_03073` composes Pattern E.1 Forced (draw on adversary destroyed) with a Gambling pure-calc `+1 Thrust` Maneuver. `_03035` has both a pressure Reaction and a multi-step C.3 Maneuver. `_03036` composes a Finesse cost discount with a Duelist C.4 Maneuver (line-count Riposte + conditional discard). `_03045` has a plain Action (claim-control move) and a Gambling Maneuver (wound participant + Riposte). `_03046` has two Pattern D.2 intervene/engarde Reactions (Duelist + Pirate). `_03047` has dual a/b Maneuvers (Scoundrel choose-gamble + Duelist cannot-gamble). `_03048` composes a gambled cost discount with a Scoundrel C.6 move-all-threat Maneuver. `_04007` composes Pattern E (wounds cost discount on **one** of dual Maneuvers) with two pure-calc Duelist Maneuvers (`a` −3 Thrust/+2 Riposte, `b` −1 Parry/+2 Thrust). `_01025` is Pattern B.2 (Sorcerer Strega equip RiskAttachment + end-of-HD destroy Forced on Burden). `_04008` composes Pattern B.2 (equip to **target** opposing non-Leader) with Pattern E.3 (blank text box via `FATES_SILENCE_CONDITION`) and attachment Forced destroy. `_01083` is a single City Action only.

## Finish (short)

1. Walk each printed Text clause → exactly one pattern (see shape table). Parse literal wording traps (they/you claim, wound participant vs adversary, intervene ≠ ChallengeAccepted, mandatory claim-on-refuse ≠ optional Reaction, bullet-If headcount/location-count/claim-control as availability filter not post-branch, "fewer locations than an opponent" = exists opponent with more (strict `<`), pressure-then-claim ≠ refuse-claim (A.8 vs A.5), Unique "Send to The Locker" after pressure = always not success-gated, "for each opposing character" = duel location not global in-play, reveal-for-stat then sink/reorder = **unchosen only**, may-engage/ignore-costs = pay-state Reaction not Action engage-at-announce, **"City Reaction:" on a Risk** = hand RiskReaction + city-character gate (not a new base class), **"After an opponent passes • They must move…"** = D.1.1 (`EventHighDramaPhasePlayerPassed` + hide when no legal Home en garde; reaction buttons over GameStates), **"Swap your participant…"** = C.9 (`swapParticipantsInDuel` + Harpoon activate/confirm; Lodestone/Shackles do **not** block swaps), **"Discard threat in excess of adversary's duel-stat"** = C.6 Parry excess via `CHALLENGE_STAT` (not remove-all, not Riposte; `_03070`), **"If this location is controlled by an opponent • Engage an opposing character"** = A.9 (claim-control If + engage `!Engaged` opposing; no Target → no Cesca; `_03071`), **"Destroy all engaged attachments … target opposing • Then engage each of their equipped"** = A.10 (Cesca Target + snapshot destroy engaged then engage remaining; `_03072`), **"Maneuver: Destroy all engaged attachments on the adversary"** = pure-resolve unequip+discard all engaged (no chooser; contrast `Technique_02026b`), **"This card has -1 cost if your Leader is …"** with Action only = E.2 (`getActionFromHandDiscount` — do **not** invent a Maneuver; `_03071`/`_01159`/`_01160`), **"While the adversary has more wounds … -1 cost"** = E via Maneuver with `$adversary->Wounds > $actor->Wounds` (`_04007` — not Modified stats), **card-level discount + dual Maneuvers** = put `getManeuverFromCombatCardDiscount` on **exactly one** a/b (`Card` sums — `_04007`), **"Equip this card to …"** = B.2 (`createRiskAttachment` + FakeAttachment; Forced "if equipped • Destroy" on the **attachment**, not Risk E.1; `_01025`/`_04008`), **"This ability cannot be copied"** = do **not** add to Cesca `Reaction_01008` allow-list (opt-in) even when printed "target" forces Cesca interfaces, **"treats their text box as blank"** = E.3 (`FATES_SILENCE_CONDITION` + Theah core-only Character dispatch; `_04008`), etc.) before copying a mirror.
2. Constructor: `initializeFaction`, matching `CardNumber`, `WealthCost`, combat stats / `DashedX`, Traits in `TraitNames::$TraitsJson` (use `Bureaucracy`, not the stub typo `Beauracracy`).
3. Ability files in `actions/` / `maneuvers/` / `reactions/`; mark `IRiskThatTargetsCharacters` on the Risk only when printed text says **"Target"/"target"** (paired with `IAbilityThatTargetsCharacters` on the ability). Skip location-only / fixed-trigger / hand-discard / no-"target"-wording choosers (`_03060`, `_03069` swap, `_03071` engage opposing).
4. Wire states + JS when you add card-specific sub-states — see [wiring.md](wiring.md).
5. Satisfy pre-commit literals (ActionResolved / ManeuverCanceled / hand `==` / setUsed / Sorcerer start+played).
6. `php -l` on touched PHP.

**Deep checklist (hooks, challenge correlators, C.1–C.9 / D.* footguns):** [checklist.md](checklist.md)
