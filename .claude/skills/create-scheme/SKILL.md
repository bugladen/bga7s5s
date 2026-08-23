---
name: create-scheme
description: Implement or finish a Scheme card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Scheme). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Scheme, or when they reference a card whose class extends Scheme and has unimplemented Text. Triggers on phrases like "implement this scheme", "finish _NNNNN" (when it extends Scheme), "wire up the When Revealed effect", "add the Renown adds to this scheme", or natural-language descriptions of a scheme card (Initiative + Panache modifier, lives in the player's scheme deck, revealed at Dawn).
---

# Creating a Scheme

This skill covers cards that extend `Scheme` — the cards a player chooses each turn and reveals during the Planning Phase. They are **not** city-deck cards; each player has their own scheme deck and selects one scheme per turn.


## How to use this skill (progressive disclosure)

1. Confirm `extends Scheme`. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** - do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Resolve via EventResolveScheme |
| [pattern-b.md](pattern-b.md) | When-Revealed effect |
| [pattern-c.md](pattern-c.md) | Multi-player sequential loop |
| [pattern-f.md](pattern-f.md) | Forced at End of Planning |
| [wiring.md](wiring.md) | GameState class, states.inc, JS |
| [reactions.md](reactions.md) | Reactions on Schemes |
| [actions.md](actions.md) | Actions / City Actions on Schemes (incl. Pattern L) |
| [walkthroughs.md](walkthroughs.md) | worked examples (_03005.._04005) |
| [helpers.md](helpers.md) | style + helpers |
| [references.md](references.md) | exemplars |
| [checklist.md](checklist.md) | full finish checklist |

When in doubt, mirror a reference rather than invent.

> **Sibling skills:** `create-character`, `create-city-character`, `create-city-event-card`, `create-city-attachment`. A *lot* of runtime semantics overlap (button-based Reactions, state classes, JS wiring) — read the relevant sibling skill alongside this one when the scheme has an Action or Reaction shape that closely matches a non-scheme card.

## Base Anatomy

`Scheme extends Card`. It adds two fields beyond `Card`:

- `$Initiative` — int. Lower = earlier in the scheme-resolution order. Required.
- `$PanacheModifier` — int. Added to (or subtracted from) the controller's Leader Panache while the scheme is revealed.

Schemes also have a `hasWhenRevealedEffect(): bool` hook (default `false`).

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _NNNNN extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';   // or '_7s5s' / 'tac'
        $this->ExpansionNumber = 3;
        $this->CardNumber      = N;

        $this->initializeFaction('Vodacce');

        $this->Initiative      = 91;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate('Villainous'),
            clienttranslate('Duress'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();
    }
}
```

Field notes:
- **`initializeFaction(...)` is mandatory** — schemes belong to a faction deck.
- **`CardNumber` matches the `NNNNN` in the filename.**
- **Initiative / PanacheModifier:** verify against card art (sun / hat icons). Scaffold stubs sometimes copy-paste wrong Initiative from another card (e.g. Blood Money scaffold had `64` — art is `8`).
- **Traits must exist in `TraitNames::$TraitsJson`** (`modules/php/TraitNames.php`). Add missing ones in alphabetical order. (Memory feedback.)
- **Verify Traits against card art / TraitNames.** Scaffold stubs sometimes misspell traits (e.g. Curry Favor had `Beauracracy` → correct is `Bureaucracy`) or use a trait not yet in the JSON (e.g. Blood Money `Assassination`). Cross-check the JPG in `misc/Assets/jpg/image_store/` and `TraitNames::$TraitsJson` before shipping.
- **`Initiative` is non-zero.** It's the tie-breaker (alongside Leader Panache) for scheme resolution order during planning. Don't leave at the constructor default 0.

### Scheme location lifecycle (read this before writing Forced / Action / Reaction gates)

Chosen schemes sit at **`Game::LOCATION_PLAYER_HOME` for the rest of the day** after Planning reveal/resolve. At Dusk they are sent to the Locker (`stDuskPhase*` clears `selected_scheme_id` and queues `createCardSentToLockerEvent`). They do **not** go to the discard pile after resolve.

Implications:
- **Forced / Action / Reaction gates** that need "is this the chosen scheme?" use `$this->Location == Game::LOCATION_PLAYER_HOME` (canonical: `_01098`, `_03041`).
- **`SchemeCityAction` / `SchemeAction`** base availability checks `LOCATION_PLAYER_HOME` — that is correct; do not "fix" it to discard.
- **Scheme reactions fire through High Drama** because the card is still in `$theah->cards` at Home (and also after Dusk via locker/discard builds — see Reactions section). Don't add "is the scheme still in play" guards.


## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. The clauses above the horizontal rule (`<hr>`) are the **scheme effect** (resolved automatically during planning). Clauses below the rule are usually **City Action / Action / Reaction / City Reaction** keywords — the same shapes as on Characters.

| Card phrase | Pattern |
|---|---|
| **"Add a Renown to [Location]"** / **"Move a Renown from X to Y"** | Pattern A — resolve via `EventResolveScheme`. Queue `createRenownAddedToLocationEvent` / `createRenownRemovedFromLocationEvent`. No state class if the choice is forced; add a state class if the player picks the source/target. |
| **"When this scheme is revealed, …"** | Pattern B — When-Revealed effect. Override `hasWhenRevealedEffect()` to `true` AND handle `EventCardWhenRevealedEffect` in `handleEvent`. The When-Revealed fires *before* the resolve (and before other schemes' resolves), per card text. |
| **"Put a card from your discard into your hand"** / **"Search your discard for X"** | Pattern A resolve with a transition to a discard-pick state. New state class + JS wiring (chooseList). Reference: `_01044`, `_03005`. |
| **"Add a Renown to a city location"** (player choice) | Pattern A resolve with a transition to a location-pick state. JS uses `makeCityLocationSelectable` / `onCityLocationsSelected`. Reference: `_01071`, `_01072`, `_02046`. |
| **"Add a Renown to [Fixed Location] and another location"** | Pattern A: queue fixed Renown first, then one planning pick state whose `locationIds` **exclude** the fixed location. Do **not** use `actCityLocationsForReknownSelected` (that helper is for N free picks with no fixed destination). JS: `locationIds` from args + `actFromCardWithLocations`. Reference: `_04014` (Forged for Battle — Docks + another). |
| **"Add a Renown to two different locations"** | Single-state two-location pick — use the framework helper `actCityLocationsForReknownSelected` and set `numberOfCityLocationsSelectable = 2` in JS. The helper iterates the JSON array, validates distinctness server-side (throws `UserException` if duplicates submitted), and queues one Renown event per location. JS also enforces distinctness as the first line of defense. **Also** add the state name to `PlayerActions.js` `actionMap` → `'actCityLocationsForReknownSelected'` (Confirm button calls `onCityLocationsSelected`, which looks up that map). Reference: `_01098`, `_03006`, `_03017`, `_03030`, `_03053`, `_04015`. |
| **"Then, move your \<Trait\> to a City location"** (after fixed Renown adds) | Pattern A with **two** planning resolve states: (1) pick owned traited character in play who has ≥1 other City dest; (2) pick City destination (exclude current location). Stash character id in `Game::CHOSEN_CARD` (not `CHOSEN_PERFORMER` — that global is HD-action owned). Queue Renown first, then `createTransitionEvent(..., "NNNNN")` at `MEDIUM_PRIORITY` **only if** ≥1 eligible character — "Then" is contingent; skip the pick states (notify) when none. `createCardMovingEvent(..., engage=false)` unless Engage is printed. Named success on state 2 (`"locationChosen"`) when `"back"`/`"zombie"` also exist. Reference: `_04004` (Blood Money). |
| **"Then, move your \<Trait\> there"** (after Renown to a **fixed** location) | Pattern A with **one** planning resolve state: pick owned traited character whose `Location !=` the fixed dest. Move immediately on `actFromCardWithId` (`engage=false`). No `CHOSEN_CARD` / second state. Contingent Then — skip transition when none eligible. Named success (`"diplomatChosen"`) when `"zombie"` also exists. Reference: `_04024` (Diplomatic Envoy — Forum). |
| **`<b>En Garde:</b> \<Trait\>s at [Location] cannot be issued [Combat] challenges`** | Passive on the **scheme class** via `eventCheck` on `EventChallengeIssued`. Gate: scheme at `LOCATION_PLAYER_HOME`; defender has trait; at printed location; `!$Engaged` (en garde); `CHALLENGE_STAT == STAT_COMBAT`. No "your" → any matching Diplomat (not controller-scoped). Influence/Finesse still legal. Character siblings: Patricia `_01095` (any challenge), Térence `_03028` (Combat issue ban). Reference: `_04024`. |
| **"Spend a Renown"** (no location named) | **Player score cost**, not a location token. Gate `getPlayerReknown($playerId) >= 1`; queue `createPlayerLosesReknownEvent($playerId, 1)`. Contrast: "Remove a Renown from [Location]" / "from this location" → `createRenownRemovedFromLocationEvent`. Reference: `Action_03053`, `Action_01168`, `Action_01139`. |
| **"Claim your performer's location"** (no Pressure / challenge) | **Direct claim** via `createLocationClaimedEvent($playerId, $performerId, $performer->Location)`. Gate performers with `$theah->canLocationBeClaimedBy($playerId, $performer->Location)` when Claim is the payoff (do not offer a dead spend). Recheck at resolve; if blocked, notify `"${location} cannot be claimed."` and still run any separate trailing effects. Reference: `Action_03053`, `Action_01103a`, `Action_02029`. |
| **"Each opponent draws a card"** | Loop `loadPlayersBasicInfos()`, skip self, queue `createCardDrawnEvent($opponentId, $owner->getInjectCode())` per opponent. No sub-state. Reference: `Action_03053`. |
| **"Each player discards a card"** (after an HD action resolves) | **Pattern L — concurrent multi-player discard.** `MULTIPLE_ACTIVE_PLAYER` HD state. **"Each player" includes the acting player** — do **not** use `stMultiPlayerInitSansInitiatingPlayer` (that is opponents-only, Patricia `01095`). Custom `onEnteringState`: `setPlayersMultiactive` only players with ≥1 hand card via `getGameDeckObject()` (Game `$cards` is private from State classes). Queue `createActionResolvedEvent` **before** the discard Transition (priority 3 before 8 — Action_01095b). Skip the discard transition when nobody has a hand card. Reference: `_04005` / `State_highDramaPhase04005_2`. |
| **"Destroy another character you control at your performer's location • Claim …"** | **Pattern L** (often stacked with each-player discard). Not Pattern H — needs an HD character-pick after framework performer select. `IAbilityThatTargetsCharacters`; destroy the **target** (contrast `Action_01015`, which destroys the **performer** as cost). Unequip before `createCharacterDestroyedEvent` — destroy recreates the card and does not auto-unequip. Claimability still gates performers when Claim is a payoff. Reference: `Action_04005`. |
| **"After your character at a `City` location is destroyed"** | Reaction listening on `EventCharacterDestroyed`. Gate by `$destroyed->ControllerId == $owner->ControllerId` and `$theah->locationInCity($destroyed->Location)`. `EventCharacterDestroyed` has `runEventHubAfterCards = true` so `$destroyed->Location` is still the destroy-time city slot when the reaction sees the event — capture it onto a `private string $location` because by the time the player clicks, the character has been moved to the locker. Reference: `_03017` (Noble Sacrifice), `Reaction_01013` (Red Hand destroyed). |
| **"When an opposing character is destroyed • …"** (often trait-prefixed Reaction) | `EventCharacterDestroyed`. **"Opposing" = different controller AND same location** (helpers.md — not any enemy anywhere). For **"Duelist Reaction"** / similar with no named performer in the trigger: require a controlled traited character **at `$destroyed->Location`** (`getCharactersAtLocation` + `hasTrait`). Destroy-time Location is still readable during `handleEvent`. Pass without `setUsed`. Reference: `Reaction_04004` (Blood Money). |
| **"Then, each opponent does X"** | Pattern C — multi-player **sequential** loop (one activeplayer transition per opponent). Distinct from Pattern L concurrent multi-discard. Reference: `_01151`. |
| **`<b>City Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending **`SchemeCityAction`**. Requires a city performer unless the card says otherwise. See action-base table in [actions.md](actions.md). |
| **`<b>Action:</b>`** (on a scheme; **not** City Action) | Extend **`SchemeAction`**, keep `RequiresPerformerSelected = false`. No framework performer pick — first HD state is whatever the text targets (location, card, …). Do **not** use `SchemeCityAction` (that base also requires a character in the city). Reference: `_04015` (Through Thick and Thin). |
| **`<b>City Reaction:</b>` / `<b>Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Pre-commit hook enforces `setUsed`/`isAvailable` literal calls. Reference: `_02004` (City Reaction), `_03005` (Reaction), `_03006` (multi-stage Reaction). |
| **Unlabelled "When …, you may …"** (no `<b>Reaction:</b>` keyword; often parenthetical "any number of times per day") | Still a **Continuous CardReaction** when there is a player choice — same as character Pattern D Continuous. Do **not** call `setUsed(true)`; satisfy pre-commit with the literal in a comment. Once-per-trigger is natural (one transition per firing event). Reference: `Reaction_04014` (Forged for Battle), character siblings `Reaction_03025` / `Reaction_01040`. |
| **"When your character issues a challenge or intervenes, you may engage a Weapon or Armor … +1[Finesse] for the duration of the action"** | Continuous Reaction on `EventChallengeIssued` (your challenger) **and** `EventCharacterIntervened` (your intervener). Gate ≥1 unengaged non-`FakeAttachment` Weapon/Armor. Buttons per attachment + Pass. Engage via `createCardEngagedEvent`; +1 Finesse via `createCharacterFinesseModifedEvent` **plus** a named `Game::…_CONDITION` + Started/Ended notifs (Soline/Harpoon — chip alone does not name the source). Clear on `EventActionResolved` gated `!IN_DUEL` (mid-duel ActionResolved must not wipe Finesse needed for gambling — same WHY as `Action_04009`). Safety clear at Dusk. Reference: `Reaction_04014`. |
| **`<b>Strega Reaction:</b>`** / **`<b>Mercenary City Action:</b>`** / **`<b>Diplomat …:</b>`** / **`<b>Duelist …:</b>`** / **`<b>Musketeer …:</b>`** / **`<b>Hero …:</b>`** / **`<b>Villain …:</b>`** / **`<b>Scoundrel …:</b>`** / **`<b>Red Hand …:</b>`** | Trait-prefixed keywords are **mechanical performer-trait gates**, NOT Sorcerer abilities. The chosen performer must have that trait (enforce via `hasTrait("Strega")` / `"Duelist"` / `"Hero"` / `"Villain"` / `"Scoundrel"` / `"Red Hand"` etc.). Do NOT `implement ISorcererAbility` for these. When the Reaction has no pickable performer in the trigger text, search for a controlled traited character (at the relevant location if the text says opposing). Reference: `_03006` (Strega), `_04004` (Duelist), `_04005` (Red Hand), `_03061` (Hero), `_03062` (Villain), `_03063` (Scoundrel). |
| **`<b>Sorcerer City Action:</b>` / `<b>Sorcerer Reaction:</b>`** | Mechanical "Sorcerer" keyword — class additionally `implements ISorcererAbility`, must emit `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). Can stack with trait gates: "Sorcerer Strega …" is both. Reference: `Reaction_02001` (Andriana), `Action_03029` (scheme Sorcerer City Action). |
| **`<b>Forced:</b>`** (event-driven, no player choice) | Override `handleEvent` on the scheme class. No Action/Reaction files. May still need a state if the Forced itself requires a pick. Reference: `_02052`'s Forced (`EventCharacterDestroyed` at Bazaar during duel). |
| **`<b>Forced:</b> At the end of Planning • …`** | **Pattern F.** Listen on `EventPhasePlanningEnd` with `$this->Location == LOCATION_PLAYER_HOME`. If the Forced needs a player pick, queue `createTransitionEvent(..., "NNNNN")` into a `PLANNING_PHASE_END_<NNNNN>` GameState registered under **`PLANNING_PHASE_END_EVENTS.transitions`** (state id `28<NNNNN>`). Do **not** put it on the resolve-schemes map. Reference: `_01098` (pick opponent), `_03041` (draw then discard). |
| **`<b>Forced:</b> At the end of High Drama, …`** | Listen on `EventHighDramaPhaseEnd` with `$this->Location == LOCATION_PLAYER_HOME` (schemes) or `cardInCity` (city cards). Player picks use `HIGH_DRAMA_END_<NNNNN>` (`60<NNNNN>`) under **`HIGH_DRAMA_END_EVENTS.transitions`**. Same card-number key may also exist under HD-turn / Planning maps — separate lookups. Reference: `_03061` (wound order), `_03cd12` (no-pick city Forced). |
| **"Draw two cards, or three … if you control an \<Trait\>. Then, discard an equal number"** | Pattern F draw-then-discard. Check trait via `getCharactersInPlayByPlayerId` + `hasTrait(...)` (includes Home + Leaders). Clamp draw count to drawable cards (faction deck + discard). Persist `$cardsToDiscard` on the scheme. Queue N `createCardDrawnEvent` **before** the discard transition so the hand UI has the new cards. Multi-select discard via `actFromCardWithIds`. If 0 drawable, notify and skip the discard state — "equal number" of 0 draws is 0 discards (do not force-discard the existing hand). Reference: `_03041`. |
| **"After you claim a location • Move a Renown from that location to a different location"** | City Reaction on `EventLocationClaimed` gated by `$event->playerId == $owner->ControllerId` and claimed location `Renown > 0`. Capture `$event->location`. Destination = button list of other city locations + Pass (no sub-state). Move with batch `createRenownMovingBetweenLocationsEvent` + remove + add(`isMove=true`). Pass does not `setUsed`. Surface the claimed location name in `getReactionDescription`. Reference: `Reaction_03041`, move-Renown button idiom `Reaction_01118`. |
| **"Choose one: <i>Either</i> … <i>or</i> …" on a City Action** (scheme or character) | **Pattern D — branch-first multi-step High Drama action.** State 1 = button pick (`actFromCardWithId` with ids 1/2); state 2 = character pick; state 3 = location pick only if one branch needs it. Persist the chosen branch on the **action object** (`public int $MoveMode`) and flush with **`$game->updateCardObjectInDb($owner)`** — `IsUpdated` alone is not flushed before `stRunEvents` rebuilds from DB. Do NOT use `Game::CHOSEN_TARGET` for branch state — the challenge framework owns that global. Use `Game::CHOSEN_CARD` to pass the chosen character between steps 2→3 when a later location pick is needed. Reference: `_03029`, `Reaction_03cd18` (same branch UX, but reactions use `$stage` + `createReactionTransitionEvent`, not HD sub-states). |
| **"When an opponent equips a card to a character opposing your \<Trait\>, it gains +1 cost"** | Passive on the **scheme class**: override `getEquipDiscount` and return `$discount -= 1` (negative discount = cost increase). Gate: scheme at `LOCATION_PLAYER_HOME`; performer is opponent (`isNotControlledByPlayer`); **`cardInCity($performer)`** (Home shares one location string across players — without this, Home equips false-positive); owned traited character at `$performer->Location`. Theah already iterates every card in `$theah->cards` for discounts, so chosen schemes at Home are included. Reference: `_03063` (Scoundrel), character parallel `_01092` (Makepeace). |
| **"Move a Renown or an available attachment from your performer's location to a different City location"** | **Pattern J — choose-what-then-destination.** Two HD states. State 1: Renown button (`actFromCardWithId` **id `0`** — card ids are never 0) and/or attachment highlight + Confirm. State 2: other City locations. Persist `$MoveMode` via `updateCardObjectInDb`; stash attachment id in `CHOSEN_CARD`. Renown move = batch `createRenownMovingBetweenLocationsEvent` + remove + add(`isMove=true`). Attachment move = `createCardMovingEvent(..., engage=false)`. Available = `getAvailableAttachmentsAtLocation`. Performers: trait gate + location has Renown>0 or ≥1 available attachment. Reference: `_03063`. |
| **"Wound your performer • Muster … from The Locker … At the end of Dusk, send them to The Locker"** | **Pattern K — locker muster + dusk return.** Trait-gated City Action + HD locker `chooseList`. Grant temporary traits **after** `EventCharacterMustered` (pending id on action + `updateCardObjectInDb`). **Dusk return cannot live on Action/Scheme** — chosen schemes are already in the locker before `EventDuskPhaseEnd`, and `buildCity()` does not load locker cards. Stamp a condition on the **mustered Character**; Character `handleEvent` on `EventDuskPhaseEnd` strips granted traits, unequips, queues locker. Reference: `_03062`. |
| **"Move your character at any location to your performer's location"** | Porté-style pull: `getCharactersInPlayByPlayerId($playerId)` filtered to `Location != $performer->Location` (includes Home). Single-mode cards can use one state like `Action_01085`. Reference: `Action_01068`, `Action_01085`. |
| **"Move your character at your performer's location to any location"** | Porté-style push: characters at `$performer->Location`, destinations = all `getCityLocations()` names + `Game::LOCATION_PLAYER_HOME` (if not already Home), excluding current location. Reference: `Action_01093` (Maya "any location"), `Action_01068`. |
| **"Engage your performer • Your \<trait\> at this location issues a [Combat] challenge"** | **Pattern E — split performer and challenger.** Framework performer pick (trait-gated Diplomat/etc.) → engage performer → HD sub-state pick challenger at that location → sub-state pick target → challenge. `CHOSEN_CARD` preserves performer id; `CHOSEN_PERFORMER` becomes challenger for challenge framework. Reference: `_03030` (Diplomat + Duelist), character parallel `Action_03003` (Thug issues challenge). |
| **"Only \<trait\>s may intervene"** on a challenge | Add a new `Game::…_CHALLENGE_TYPE` constant. Enforce in **`Theah::interventionCheck`**, **`ArgumentsTrait`** (intervene-picker `ids`), and **`Reaction_02058`** (adjacent external intervene) — same trio as `LEGENDARY_REPUTATION_CHALLENGE_TYPE` / `AJA_CHALLENGE_TYPE`. Reference: `_03030` (`SWORN_SWORDS_CHALLENGE_TYPE`, Duelist gate). |
| **"If the challenge is accepted, add a threat to your participant"** | Listen on `EventGenerateChallengeThreat` in the action class; bump `$event->actorThreat` only (not adversary). Fires on accept/intervene path when threat is generated, not on refuse. Reference: `Action_03030` (+1 actor), contrast `Action_02061` (+1 both). |
| **"If your performer is a Duelist, it can only be refused by discarding a card"** | **Pattern G — discard-to-refuse.** See full section below. Correlator `CHALLENGE_TYPE` (out of auto-engage) + refuse routed through card-keyed HD discard state. Reference: `_03042` / `Action_03042`. |
| **"Spend a Renown • Claim your performer's location. [trailing effects]"** | **Pattern H — immediate-resolve City Action.** Framework performer pick only; all effects fire on `EventActionTriggered`. No `HIGH_DRAMA_PLAYER_TURN_*` GameState. Reference: `_03053`. |
| **"unequipped performer"** / **"Your unequipped performer …"** | Gate `count($performer->Attachments) == 0` in `getPerformersForAction` / availability. Same check as `Action_01131` (Iron and Velvet). Re-validate on `EventActionTriggered`. |
| **"Wound your [unequipped] performer • Pressure their location with [Stat]"** | **Pattern I — wound-then-pressure.** Queue performer wound, then `PRESSURE_STAT` + `createPressureOccuringEvent` + transition `"pressureLocation"`. **Must** capture city location in `CHOSEN_LOCATION` and clear `CHOSEN_PERFORMER` before pressure (lethal wound → locker before `stHighDramaPressureLocation`). Stash original performer id in `CHOSEN_CARD` for post-pressure UI. Reference: `_03054`. |
| **"If successful, wound target opposing character and move them Home"** (after pressure) | On `EventLocationPressureResult` success: if opposing remain at `$event->location`, transition to HD pick state; else notify + `createActionResolvedEvent`. On pick: queue wound, then Home move **only if** `$target->Wounds + 1 < $target->ModifiedResolve` (skip move when lethal — avoid locker→Home yank). Failure / no-target paths must still queue `createActionResolvedEvent` (do not copy `Action_01105`'s failure gap). Reference: `Action_03054`. |
| **"Move your performer to a location with a wounded enemy"** | Trait-gated `SchemeCityAction` (often **Duelist**). HD location pick only. Destinations = other City locations that have ≥1 opposing character with `Wounds > 0`. `getPerformersForAction` = trait + `count(destinations) > 0`. `createCardMovingEvent(..., engage=false)` + `createActionResolvedEvent`. Named success transition when `"zombie"` (or `"back"`) also exists. Reference: `Action_04004`. |
| **"Target an uncontrolled City location • Move your \<Named Character\> … heal … Then you may discard an available City Card"** | **Pattern M — Scheme Action (no performer).** `SchemeAction` + `RequiresPerformerSelected = false`. HD state 1: uncontrolled city pick (`Controller == 0`). Match named characters by **`$character->Name === clienttranslate('…')`**, not CardNumber — reprints share Names across ids. Complete-as-much-as-possible: move/heal each found character; skip missing. Heal only if `Wounds > 0`; skip move when already at target. Optional discard = HD state 2 + Pass; skip state 2 when no discardable card. Available City Card = `ICityDeckCard` + `!isControlled()` + `canBeDiscardedFromCity()` at that location. `ActionResolved` after discard/pass (or when skipping discard) — not Pattern-L ordering. Stash location in `CHOSEN_LOCATION`. Reference: `_04015` / `Action_01112b` (discard filter). |

A single scheme can combine these freely. `_03005` has a two-clause resolve (Renown adds + pick-from-discard) AND a Reaction. `_01044` has a resolve (Renown + pick attachment) AND a City Action. `_02014` has a one-clause resolve (add OR move Renown) AND a Leader City Action. `_03029` has a trivial Renown resolve AND a branched Sorcerer City Action. `_03030` has a two-location resolve AND a split-performer Combat challenge with intervention gate and accept-time threat. `_03041` has a trivial Renown resolve AND a Forced draw/discard at Planning End AND a claim→move-Renown City Reaction. `_03042` has a trivial Renown resolve AND an engage→Finesse challenge City Action with conditional discard-to-refuse. `_03053` has a two-location resolve AND an immediate-resolve spend→claim→opponents-draw City Action (Pattern H). `_03054` has a trivial dual Renown resolve AND a wound-then-Resolve-pressure City Action with success target wound+Home (Pattern I). `_03061` has a trivial dual Renown resolve AND a Hero City Action AND a Forced wound-order at High Drama End. `_03062` has a trivial dual Renown resolve AND a Villain locker-muster City Action (Pattern K). `_03063` has a trivial dual Renown resolve AND a passive equip tax AND a Scoundrel Renown-or-attachment move City Action (Pattern J). `_04004` has fixed dual Renown + move-your-Duelist planning pick AND a Duelist City Action (move to wounded-enemy location) AND a Duelist Reaction (opposing destroyed → draw). `_04005` has trivial Docks Renown AND a Red Hand City Action (Pattern L: destroy controlled character → claim → each player discards). `_04014` has fixed Docks Renown + pick-another-location resolve AND a Continuous challenge/intervene engage-Weapon/Armor → +1 Finesse Reaction. `_04015` has two-different-locations Renown resolve AND a no-performer Scheme Action (Pattern M: uncontrolled city → name-matched Kaspar/Daniella move+heal → optional available City Card discard). `_04024` has fixed Forum Renown + Then move your Diplomat there (one pick state) AND an En Garde Combat-challenge sanctuary at Forum. |


## Finish (short)

1. Walk each printed Text clause to exactly one pattern (see shape table).
2. Match constructor fields / Traits / CardNumber to the printed card.
3. Put abilities in the correct subdirectory files; wire states + JS when needed - see companions.
4. Satisfy pre-commit literals; run `php -l` on touched PHP.
5. Schemes: set Initiative + PanacheModifier; prefer GameState-class resolve states for new work.

**Deep checklist:** [checklist.md](checklist.md)
