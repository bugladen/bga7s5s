> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Walkthrough: implementing `_03005` (No Mercy)

A concrete worked example combining most patterns above. Card text:

> Add a Renown to [The Grand Bazaar] and [The City Forum]
> Put a **Gang**, **Crime**, or **Villainous** card from your discard into your hand.
> **Reaction:** After your **Red Hand**'s challenge is refused • Claim that location.

1. **Constructor.** `initializeFaction('Vodacce')`, set `Initiative = 91`, `PanacheModifier = -1`, Traits = Villainous + Duress. Both traits already in `TraitNames::$TraitsJson`.
2. **Resolve.** `EventResolveScheme` handler queues `createRenownAddedToLocationEvent` for Bazaar and Forum, then a `createTransitionEvent($playerId, $this->Id, "03005")` with `MEDIUM_PRIORITY` to move into the discard-pick state.
3. **Discard-pick state.** New GameState class `State_planningPhaseResolveSchemes03005` in `States/faf/`. `#[PossibleAction]` for `actFromCardWithId(int)` and `actFromCardPass()`. `zombie()` calls `nextState()`. **No `ZombieTrait.php` edit.**
4. **State constant.** `States::PLANNING_PHASE_RESOLVE_SCHEMES_03005 = 2603005`.
5. **Transition map.** `"03005" => States::PLANNING_PHASE_RESOLVE_SCHEMES_03005` in `states.inc.php`'s `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`.
6. **Scheme class methods.** `actFromCardWithId` validates the card is in discard AND has Gang/Crime/Villainous trait, queues remove-from-discard + add-to-hand, `nextState("")`. `actFromCardPass` throws if any eligible card remains in discard.
7. **JS.** Add to `OnEnteringState.faf.js` (populate `chooseList` from `player.discard` filtered by `card.traits.includes(...)`), `OnUpdateActionButtons.faf.js` (Confirm + Pass), `OnLeavingState.faf.js` (hide and `chooseList.removeAll()`).
8. **Reaction.** `Reaction_03005 extends CardReaction`. Listens for `EventChallengeRejected` where the challenger is controlled by the scheme owner AND has the "Red Hand" trait. Captures `$challenger->Location` onto `$this->location`. Queues `createReactionTransitionEvent`. `performReaction` queues `createLocationClaimedEvent($owner->ControllerId, null, $this->location)` and `setUsed($theah, true)` if the player clicks "Claim …"; clears `$this->location` and `nextState("done")` either way.
9. **Pre-commit compliance.** `Reaction_03005` calls `$this->setUsed(` and `$this->isAvailable(` — hook satisfied.

Full implementation lives at `modules/php/cards/faf/_03005.php`, `modules/php/cards/faf/reactions/Reaction_03005.php`, `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php`.

## Walkthrough: implementing `_03029` (Hour of Blood)

Card text:

> Add a Renown to [The City Forum] and [The City Docks]
> **Sorcerer City Action:** Wound your performer • Choose one: *Either* move your character at any location to your performer's location, *or* move your character at your performer's location to any location.

1. **Constructor.** `initializeFaction('Montaigne')`, `Initiative = 71`, `PanacheModifier = 0`, Traits = Sorcery + Porté. Register `IHasActions` + `ActionTrait` + `new Action_03029()`.
2. **Resolve.** `EventResolveScheme` queues two `createRenownAddedToLocationEvent` (Forum + Docks). No sub-state — both destinations are fixed.
3. **Action class.** `Action_03029 extends SchemeCityAction implements ISorcererAbility`. `RequiresPerformerSelected = true`. `getPerformersForAction` filters Sorcerer trait. `isAvailableToPlayer` checks each Sorcerer performer for option A and/or B legality.
4. **Branch persistence.** `public int $MoveMode` on the action (values 1 = to performer, 2 = from performer). Set in state 1, read in state 2 args filtering, clear after resolve. `$owner->IsUpdated = true` when mutating.
5. **Three HD sub-states.**
   - `03029`: buttons for each available branch (`optionToPerformerAvailable` / `optionFromPerformerAvailable` in args). `actFromCardWithId(1|2)` → `nextState("optionChosen")`.
   - `03029_2`: character picker filtered by `$MoveMode`. Option A resolves here (wound + move + sorcerer events + `createActionResolvedEvent`). Option B stores `Game::CHOSEN_CARD` → `nextState("characterChosen")`.
   - `03029_3`: location picker (city + Home via `makeHomeEndcapMarkerSelectable` in JS). `actFromActionWithIds` → resolve.
6. **State constants.** `HIGH_DRAMA_PLAYER_TURN_03029 = 403029`, `_03029_2 = 4030292`, `_03029_3 = 4030293`.
7. **Transitions.** `"03029"`, `"03029_2"`, `"03029_3"` in `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. State 2/3 include `"back"` transitions.
8. **JS (faf).** State 1: conditional action buttons (not card selection). State 2: `highlightCardsAsSelectable` + Confirm + Back. State 3: city locations + Home + Confirm + Back. Highlight performer (and chosen character on step 3).
9. **Pre-commit.** `createActionResolvedEvent()` in resolve path; both sorcerer start/played events in `resolveMove()`.

Full implementation: `modules/php/cards/faf/_03029.php`, `modules/php/cards/faf/actions/Action_03029.php`, `modules/php/States/faf/State_highDramaPhase03029{,_2,_3}.php`.

## Walkthrough: implementing `_03030` (Sworn Swords)

Card text:

> Add a Renown to two different locations.
> **Diplomat City Action:** Engage your performer • Your **Duelist** at this location issues a [Combat] challenge to target opposing character. Only **Duelists** may intervene. If the challenge is accepted, add a threat to your participant.

1. **Constructor.** `initializeFaction('Montaigne')`, `Initiative = 36`, `PanacheModifier = 0`, Traits = Oathsworn + Challenge. Register `IHasActions` + `ActionTrait` + `new Action_03030()`.
2. **Resolve.** Same as `_03006`: `EventResolveScheme` notifies, then `createTransitionEvent($playerId, $this->Id, "03030")` with `MEDIUM_PRIORITY`. Planning state uses `actCityLocationsForReknownSelected` + `numberOfCityLocationsSelectable = 2` in JS. Transition key `"03030"` lives under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` — distinct from the HD action's `"03030"` under `HIGH_DRAMA_PLAYER_TURN_EVENTS` (same card number, different maps).
3. **Action class.** `Action_03030 extends SchemeCityAction implements IAbilityThatTargetsCharacters`. `RequiresPerformerSelected = true`.
4. **`getPerformersForAction`.** Filter Diplomats who are `!Engaged`, have ≥1 eligible Duelist at their location (`hasTrait("Duelist") && canChallenge`), AND have ≥1 opposing character at that location. `isAvailableToPlayer` = `count(getPerformersForAction) > 0`.
5. **`EventActionTriggered`.** Engage Diplomat → `CHOSEN_CARD = $diplomatId` → transition `"03030"`.
6. **Two HD sub-states.**
   - `03030`: pick Duelist → `CHOSEN_PERFORMER = $duelistId` → `duelistChosen` → state 2.
   - `03030_2`: pick target → set `SWORN_SWORDS_CHALLENGE_TYPE` + `STAT_COMBAT` → transition `"03030_2"` → `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`.
7. **`SWORN_SWORDS_CHALLENGE_TYPE = 21`.** Gate Duelist-only intervene in `Theah::interventionCheck`, `ArgumentsTrait`, `Reaction_02058`. `EventGenerateChallengeThreat`: `actorThreat += 1`.
8. **JS (faf).** Planning: copy `_03006` location picker. HD: copy `_03003` character picker (highlight Diplomat on step 1, Duelist on step 2).

Full implementation: `modules/php/cards/faf/_03030.php`, `modules/php/cards/faf/actions/Action_03030.php`, `modules/php/States/faf/State_planningPhaseResolveSchemes03030.php`, `State_highDramaPhase03030{,_2}.php`.

## Walkthrough: implementing `_03041` (Proper Study)

Card text:

> Add a Renown to City Docks and The Grand Bazaar.
> **Forced:** At the end of Planning • Draw two cards, or three cards instead if you control an Academic. Then, discard an equal number of cards.
> **City Reaction:** After you claim a location • Move a Renown from that location to a different location.

1. **Constructor.** `initializeFaction('Castille')`, `Initiative = 68`, `PanacheModifier = 1`, Traits = Alquimia + Scholarship (already in `TraitNames`). Register `IHasReactions` + `ReactionTrait` + `new Reaction_03041()`.
2. **Resolve.** Trivial dual Renown (Docks + Bazaar). No planning sub-state.
3. **Forced (Pattern F).** On `EventPhasePlanningEnd` + `Location == LOCATION_PLAYER_HOME`: compute draw count (2 or 3 via Academic), clamp to drawable, queue draws, persist `$cardsToDiscard`, transition `"03041"` under **`PLANNING_PHASE_END_EVENTS`**.
4. **Discard state.** `State_planningPhaseEnd_03041` with `actFromCardWithIds`. Constant `PLANNING_PHASE_END_03041 = 2803041`. JS: multi hand select + exact-count `EventHandlers` enable + `onCardsDiscarded`.
5. **City Reaction.** `EventLocationClaimed` → capture location → destination buttons + Pass → batch move Renown. Gate on Renown > 0 at claimed location.

Full implementation: `modules/php/cards/faf/_03041.php`, `modules/php/cards/faf/reactions/Reaction_03041.php`, `modules/php/States/faf/State_planningPhaseEnd_03041.php`.

## Walkthrough: implementing `_03042` (When Least Expected)

Card text:

> Add a Renown to City Docks and City Forums.
> **City Action:** Engage your performer • They issue a [Finesse] challenge to target opposing character. If your performer is a Duelist, it can only be refused by discarding a card.

1. **Constructor.** `initializeFaction('Castille')`, `Initiative = 66`, `PanacheModifier = 0`, Traits = Ambush + Cunning. Register `IHasActions` + `ActionTrait` + `new Action_03042()`.
2. **Resolve.** Trivial dual Renown (Docks + Forum). No planning sub-state.
3. **Action (engage-and-challenge).** `SchemeCityAction` + `IAbilityThatTargetsCharacters`. `RequiresPerformerSelected = true`. Filter `canChallenge && !Engaged` with opposing targets. Engage in `EventActionTriggered`; set `WHEN_LEAST_EXPECTED_CHALLENGE_TYPE` + `STAT_FINESSE`; transition `"03042"` → `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`.
4. **Challenge type `23`.** Out of auto-engage list. No intervene gate. Matching int in `seventhseacityoffivesails.js`.
5. **Pattern G refuse.** `actHighDramaChallengeActionReject` + accept-challenge args (`mustDiscardToRefuse`, `defenderHandCount`). ACCEPT_CHALLENGE transition key **`"03042"`** (not a reusable name).
6. **Discard state.** `State_highDramaPhase03042` with transitions `"cardDiscarded" => GENERATE_THREAT` and `"back" => ACCEPT_CHALLENGE`. Action discards then queues `ChallengeRejected`. JS: Refuse label/disable + faf triple + EventHandlers.

**Studio bugs hit (do not regress):**
- Leaving discard with a typo'd transition name → "transition impossible at this state".
- Leaving discard with `nextState("")` while `"back"` also exists → "More than one possible transition". Fix: named `"cardDiscarded"`.

Full implementation: `modules/php/cards/faf/_03042.php`, `modules/php/cards/faf/actions/Action_03042.php`, `modules/php/States/faf/State_highDramaPhase03042.php`.

## Walkthrough: implementing `_03053` (Curry Favor)

Card text:

> Add a Renown to two different locations.
> **City Action:** Spend a Renown • Claim your performer's location. Each opponent draws a card.

1. **Constructor.** `initializeFaction('Ussura')`, `Initiative = 49`, `PanacheModifier = 0`, Traits = Trade + Bureaucracy (verify spelling against art / `TraitNames` — scaffold had `Beauracracy`). Register `IHasActions` + `ActionTrait` + `new Action_03053()`.
2. **Resolve.** Same as `_03006` / `_03030`: notify + `createTransitionEvent(..., "03053")` with `MEDIUM_PRIORITY`. State uses `actCityLocationsForReknownSelected` + `numberOfCityLocationsSelectable = 2`. Wire JS triple **and** `PlayerActions.js` `actionMap`.
3. **State constant.** `PLANNING_PHASE_RESOLVE_SCHEMES_03053 = 2603053`. Transition `"03053"` under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` only — no HD map entry.
4. **Action (Pattern H).** `SchemeCityAction`. No HD GameState. Gate score Renown + claimable city performers. On `EventActionTriggered`: `createPlayerLosesReknownEvent` → claim (or notify) → each opponent `createCardDrawnEvent` → `createActionResolvedEvent`.
5. **Pre-commit.** `createActionResolvedEvent()` present. No `ISorcererAbility` / reaction hooks.

Full implementation: `modules/php/cards/faf/_03053.php`, `modules/php/cards/faf/actions/Action_03053.php`, `modules/php/States/faf/State_planningPhaseResolveSchemes03053.php`.

## Walkthrough: implementing `_03054` (No Steel, No Surrender)

Card text:

> Add a Renown to [The City Docks] and [The City Forum].
> **City Action:** Wound your unequipped performer • Pressure their location with Resolve. If successful, wound target opposing character and move them **Home**.

1. **Constructor.** `initializeFaction('Ussura')`, `Initiative = 5`, `PanacheModifier = 0`, Traits = Kulachniy Boi + Brawl (both already in `TraitNames`). Register `IHasActions` + `ActionTrait` + `new Action_03054()`. **Verify printed locations against art** — scaffold / early text may say Grand Bazaar when the card is Forum (or vice versa).
2. **Resolve.** Trivial dual Renown (Docks + Forum). No planning sub-state — same shape as `_03029` / `_03042`.
3. **Action (Pattern I).** `SchemeCityAction` + `IAbilityThatTargetsCharacters`. Performers: unequipped + `canPressure(STAT_RESOLVE)` + ≥1 opposing at location. On trigger: stash location / performer (`CHOSEN_LOCATION`, `CHOSEN_CARD`), clear `CHOSEN_PERFORMER`, wound performer, pressure with Resolve via `"pressureLocation"`.
4. **Success pick.** `HIGH_DRAMA_PLAYER_TURN_03054 = 403054`, transition `"03054"` under `HIGH_DRAMA_PLAYER_TURN_EVENTS`. State class `State_highDramaPhase03054` with `actFromCardWithId(string $id)`. On pick: wound target; Home move only if non-lethal; `createActionResolvedEvent`.
5. **JS (faf).** Enter: highlight performer (if still set) + opposing `ids`. Buttons: Confirm only (no Pass — mandatory target when state opens). Leave: unhighlight.
6. **Pre-commit.** `createActionResolvedEvent()` on failure, success-without-target, and after successful pick.

Full implementation: `modules/php/cards/faf/_03054.php`, `modules/php/cards/faf/actions/Action_03054.php`, `modules/php/States/faf/State_highDramaPhase03054.php`.

## Walkthrough: implementing `_03062` (Deal with the Devil)

Card text:

> Add a Renown to [City Forum] and [City Docks].
> **Villain City Action:** Wound your performer • Muster one of your non-**Undead**, non-**Mercenary** characters from **The Locker** at this location. They gain **Monster** and **Undead**. At the end of Dusk, send them to **The Locker**.

1. **Constructor.** Neutral faction. Verify Traits against art (scaffold had Virtue — art is Villainous–Pact). Register `IHasActions` + `Action_03062`.
2. **Resolve.** Trivial dual Renown (Forum + Docks). No planning sub-state.
3. **Action (Pattern K).** `SchemeCityAction`. Villain trait gate (not Sorcerer). HD locker `chooseList` of eligible characters. Wound performer → muster at performer location → `createActionResolvedEvent`.
4. **Trait grants.** `$pendingMusterId` + `updateCardObjectInDb`; on `EventCharacterMustered`, add Monster/Undead, clear pending, flush again.
5. **Dusk return on Character.** Stamp a condition on the mustered character. Character `EventDuskPhaseEnd`: strip granted traits, unequip, queue locker. WHY not on Action/Scheme: scheme is already in locker; `buildCity()` skips locker.
6. **JS.** Locker chooseList; coerce ids with `Number()` both sides.

Full implementation: `modules/php/cards/faf/_03062.php`, `modules/php/cards/faf/actions/Action_03062.php`, `modules/php/States/faf/State_highDramaPhase03062.php` (+ Character condition path).

## Walkthrough: implementing `_03063` (Smuggling Run)

Card text:

> Add a Renown to [The Grand Bazaar] and [City Docks].
> When an opponent equips a card to a character opposing your **Scoundrel**, it gains +1 cost.
> **Scoundrel City Action:** Move a Renown or an available attachment from your performer's location to a different **City** location.

1. **Constructor.** Neutral, Cunning–Crime, Init 52 / Panache 0. Register `IHasActions` + `Action_03063`.
2. **Resolve.** Trivial dual Renown (Bazaar + Docks — match sack/anchor icons on art).
3. **Passive equip tax.** On the scheme: `getEquipDiscount` → `$discount -= 1` when opponent equips onto a city character at a location where you have a Scoundrel. Gates: scheme at Home, opponent performer, **`cardInCity($performer)`**, owned Scoundrel at that location. Character parallel: Makepeace `_01092`.
4. **Action (Pattern J).** Scoundrel trait gate + location has Renown or available attachment. Two HD states: choose Renown (id `0`) or attachment → choose other City location. Persist `$MoveMode` with `updateCardObjectInDb`. Renown = batch move events; attachment = `createCardMovingEvent(engage=false)`.
5. **State constants.** `HIGH_DRAMA_PLAYER_TURN_03063 = 403063`, `_2 = 4030632`. Named transitions `thingChosen` / `locationChosen` / `back` / `zombie`.
6. **JS (faf).** State 1: conditional Move Renown button + attachment highlight/Confirm. State 2: city locations + Back + Confirm.
7. **Pre-commit.** `createActionResolvedEvent()`; no `ISorcererAbility`. After Write tool, verify single CRLF (`doubleCR=0`).

Full implementation: `modules/php/cards/faf/_03063.php`, `modules/php/cards/faf/actions/Action_03063.php`, `modules/php/States/faf/State_highDramaPhase03063{,_2}.php`.
