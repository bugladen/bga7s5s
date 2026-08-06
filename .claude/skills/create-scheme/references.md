> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/Scheme.php` | Base class. `$Initiative` + `$PanacheModifier`, `hasWhenRevealedEffect()` default. |
| `modules/php/cards/_7s5s/_01044.php` (Armed and Marshaled) | **Resolve = Renown adds + pick attachment from discard.** Old inline-state pattern with `actFromCardWithId` / `actFromCardPass`. Plus a City Action. |
| `modules/php/cards/_7s5s/_01045.php` (The Song of Eisen) | Pick a Mercenary from the **city** discard pile. Same inline-state pattern as `_01044`. |
| `modules/php/cards/_7s5s/_01071.php` (Épée Sanglante) | Add Renown to a player-chosen city location. `actCityLocationsForReknownSelected`. |
| `modules/php/cards/_7s5s/_01072.php` (Réputation Méritée) | Pick a location that has no Renown. Pass guard if no such location exists. |
| `modules/php/cards/_7s5s/_01151.php` (Shifting Tides) | **When-Revealed effect** + **multi-player sequential loop.** First state is the owner's pick, second state is each opponent's pick queued per-player in turn order. |
| `modules/php/cards/_7s5s/_01098.php` (The Cat's Embargo) | **Forced at Planning End** (opponent pick → random reveal) + two-location resolve. Canonical `EventPhasePlanningEnd` + `LOCATION_PLAYER_HOME` gate; transitions under `PLANNING_PHASE_END_EVENTS`. |
| `modules/php/States/_7s5s/State_planningPhaseEnd_01098.php` | Planning-End Forced state class (opponent buttons). |
| `modules/php/cards/faf/_03041.php` (Proper Study) | **Trivial Renown resolve + Forced draw-then-discard at Planning End + claim→move-Renown City Reaction.** Pattern F + `$cardsToDiscard` persistence. |
| `modules/php/cards/faf/reactions/Reaction_03041.php` | City Reaction on `EventLocationClaimed`; destination buttons; batch Renown move; Pass without `setUsed`. |
| `modules/php/States/faf/State_planningPhaseEnd_03041.php` | Planning-End Forced discard state (`actFromCardWithIds`). |
| `modules/php/cards/tac/_02004.php` (Crash the Party) | **Scheme with a City Reaction.** Simple Renown adds resolve. `EventPressureOccuring` reaction with captured-location state. |
| `modules/php/cards/tac/_02014.php` (Kaspar's Occupation) | Two-option resolve (add OR move Renown via `actFromCardWithId`/`actFromCardWithIds`). Plus a Leader City Action. |
| `modules/php/cards/tac/_02046.php` (Winter's Wind) | **New GameState-class pattern** for the resolve sub-state. Location picker. |
| `modules/php/cards/tac/_02052.php` (Gutter Full of Roses) | **New GameState-class pattern** with a move-renown source pick. Plus a Forced ability on `EventCharacterDestroyed`. |
| `modules/php/cards/faf/_03005.php` (No Mercy) | **Renown adds + trait-filtered discard pick + Reaction.** New GameState-class pattern. Reaction on `EventChallengeRejected` with captured location and `createLocationClaimedEvent`. |
| `modules/php/cards/faf/_03006.php` (Premonition) | **Two-different-locations resolve via `actCityLocationsForReknownSelected` + multi-stage Strega Reaction.** Single state with `numberOfCityLocationsSelectable = 2`. Reaction is a trait-prefixed gate (Strega), NOT a Sorcerer ability. Multi-stage `$stage` flow: `'offer'` → `'pick1'` → `'pick2'` with cross-player `createReactionTransitionEvent` swapping active player from owner to triggering opponent. Listens to the full `IAbilityThatTargetsCharacters` event set. |
| `modules/php/cards/faf/_03017.php` (Noble Sacrifice) | **Two-different-locations resolve + after-your-character-destroyed Reaction.** Reaction listens on `EventCharacterDestroyed` gated by `locationInCity($destroyed->Location)` and friendly controller, captures `$location` + `$destroyedWasZealot` + `$destroyedName` because the destroyed character has been moved to the locker by the time the player clicks. Single button bundles all sub-effects (wound opposing chars at location + heal own chars at location + conditional draw) — no internal "may", so resolution is atomic. |
| `modules/php/cards/faf/reactions/Reaction_03017.php` | Bundled-effect scheme Reaction. Snapshots destroy-time location and trait at trigger time, queries `getCharactersAtLocation` at resolve time (so movement between trigger and click is reflected). Pass does not consume `setUsed`. |
| `modules/php/cards/faf/reactions/Reaction_03005.php` | Scheme reaction with `$location` capture, button-based Claim/Pass, `setUsed`/`isAvailable` discipline. |
| `modules/php/cards/faf/reactions/Reaction_03006.php` | Multi-stage button-driven Reaction with `$stage` field, cross-player reaction transitions (opponent becomes active for hand-picking), `IAbilityThatTargetsCharacters` multi-event listening with `sourceId=0` BasicChallengeAction fallback. |
| `modules/php/cards/tac/reactions/Reaction_02004.php` | Scheme reaction with adjacent-character target picker; captures the pressured location. |
| `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php` | Reference for the new GameState-class shape, `#[PossibleAction]` methods, and inline `zombie()`. |
| `modules/php/cards/faf/_03029.php` (Hour of Blood) | **Trivial dual Renown resolve + branched Sorcerer City Action.** No planning sub-state; three HD action sub-states for choose-one Porté moves. |
| `modules/php/cards/faf/actions/Action_03029.php` | `SchemeCityAction` + `ISorcererAbility`. `$MoveMode` branch persistence, `isValidTargetForAbility`, Sorcerer performer filter, Porté move pools. |
| `modules/php/States/faf/State_highDramaPhase03029.php` | HD action state 1: branch buttons via `actFromCardWithId`. |
| `modules/php/States/faf/State_highDramaPhase03029_2.php` | HD action state 2: character pick with `actBack`. |
| `modules/php/States/faf/State_highDramaPhase03029_3.php` | HD action state 3: location pick (`actFromCardWithLocations`) with `actBack`. |
| `modules/php/cards/faf/_03030.php` (Sworn Swords) | **Two-different-locations resolve + Diplomat/Duelist split-performer Combat challenge.** Planning + HD both use transition key `"03030"` in their respective maps. |
| `modules/php/cards/faf/actions/Action_03030.php` | Pattern E: engage Diplomat, Duelist challenges. `getPerformersForAction` checks Duelist + opponent at location. `SWORN_SWORDS_CHALLENGE_TYPE`, `EventGenerateChallengeThreat` +1 actor. |
| `modules/php/States/faf/State_planningPhaseResolveSchemes03030.php` | Two-location planning resolve (same shape as `03006`). |
| `modules/php/States/faf/State_highDramaPhase03030.php` | HD state 1: Duelist pick after Diplomat engaged. |
| `modules/php/States/faf/State_highDramaPhase03030_2.php` | HD state 2: opposing target pick → challenge technique flow. |
| `modules/php/cards/faf/_03042.php` (When Least Expected) | **Trivial dual Renown + engage→Finesse challenge City Action with Pattern G discard-to-refuse.** |
| `modules/php/cards/faf/actions/Action_03042.php` | Cornered-shaped engage + `WHEN_LEAST_EXPECTED_CHALLENGE_TYPE`; `actFromActionWithId` discard-then-reject. |
| `modules/php/States/faf/State_highDramaPhase03042.php` | Discard-to-refuse hand picker; `"cardDiscarded"` + `"back"` (named success — no `""`). |
| `modules/php/cards/faf/_03053.php` (Curry Favor) | **Two-different-locations resolve + Pattern H City Action** (spend score Renown → direct claim → each opponent draws). No HD sub-state. |
| `modules/php/cards/faf/actions/Action_03053.php` | Immediate-resolve `SchemeCityAction`: `createPlayerLosesReknownEvent`, claimability-filtered performers, opponent draws via `loadPlayersBasicInfos`. |
| `modules/php/States/faf/State_planningPhaseResolveSchemes03053.php` | Two-location planning resolve (same shape as `03006` / `03030`). |
| `modules/php/cards/faf/_03054.php` (No Steel, No Surrender) | **Trivial dual Renown (Docks + Forum) + Pattern I** wound-unequipped → Resolve pressure → wound+Home. |
| `modules/php/cards/faf/actions/Action_03054.php` | Pattern I: unequipped gate, `CHOSEN_LOCATION` / clear `CHOSEN_PERFORMER`, `EventLocationPressureResult` success pick, lethal skip Home move, ActionResolved on fail. |
| `modules/php/States/faf/State_highDramaPhase03054.php` | Post-pressure opposing-character pick (`actFromCardWithId`). |
| `modules/php/cards/faf/_03061.php` (Burn like Mice) | **Trivial dual Renown (Forum + Bazaar) + Hero City Action + Forced wound-order at High Drama End.** `EventHighDramaPhaseEnd` + `LOCATION_PLAYER_HOME`; remaining queue on `$remainingWoundIds`; transition under `HIGH_DRAMA_END_EVENTS` (not Planning End / HD turn). |
| `modules/php/cards/faf/actions/Action_03061.php` | Hero trait gate (not Sorcerer); target en garde non-Leader at any Home; move to performer (`engage=false`). |
| `modules/php/States/faf/State_highDramaEnd_03061.php` | HD-end Forced sequential wound pick; zombie wounds first remaining. |
| `modules/php/States/faf/State_highDramaPhase03061.php` | City Action Home-target pick. |
| `modules/php/cards/faf/_03062.php` (Deal with the Devil) | **Trivial dual Renown (Forum + Docks) + Villain City Action** muster from locker. Traits Villainous+Pact (not Virtue). End-of-Dusk return via Character condition (scheme already in locker). |
| `modules/php/cards/faf/actions/Action_03062.php` | Villain trait gate; wound + locker muster; Monster/Undead after `EventCharacterMustered`; `$pendingMusterId` + `updateCardObjectInDb`. |
| `modules/php/States/faf/State_highDramaPhase03062.php` | HD locker chooseList pick. |
| `modules/php/cards/faf/_03063.php` (Smuggling Run) | **Trivial dual Renown (Bazaar + Docks) + passive equip tax + Scoundrel City Action** move Renown or available attachment. |
| `modules/php/cards/faf/actions/Action_03063.php` | Pattern J: Scoundrel gate; Renown id=`0` vs attachment pick; destination city; batch Renown move; `createCardMovingEvent(engage=false)` for attachment. |
| `modules/php/States/faf/State_highDramaPhase03063.php` | HD state 1: choose Renown or attachment (`thingChosen`). |
| `modules/php/States/faf/State_highDramaPhase03063_2.php` | HD state 2: destination location (`locationChosen` + `back`). |
| `modules/php/cards/_7s5s/_01092.php` (Makepeace) | Character parallel for `getEquipDiscount` -= 1 when opposing character equips. |
| `modules/php/cards/_7s5s/actions/Action_01105.php` | Resolve-pressure success → engage pick (no wound cost). Useful parallel; **do not** copy its missing ActionResolved-on-failure. |
| `modules/php/cards/bas/_04004.php` (Blood Money) | **Fixed dual Renown (Docks + Bazaar) + Then move your Duelist (two planning states) + Duelist City Action + Duelist Reaction.** Initiative/Traits verified against art (`Assassination` added to TraitNames). |
| `modules/php/cards/bas/actions/Action_04004.php` | Duelist gate; move to other City location with wounded enemy; `engage=false`; named `"locationChosen"`. |
| `modules/php/cards/bas/reactions/Reaction_04004.php` | Opposing destroyed → draw. Opposing = enemy **at same location as your Duelist** (not any enemy). |
| `modules/php/States/bas/State_planningPhaseResolveSchemes04004.php` | Planning state 1: Duelist pick (`duelistChosen`). |
| `modules/php/States/bas/State_planningPhaseResolveSchemes04004_2.php` | Planning state 2: City dest (`locationChosen` + `back` — no `""`). |
| `modules/php/States/bas/State_highDramaPhase04004.php` | HD location pick for City Action. |
| `modules/php/cards/bas/_04005.php` (Denounced, Disgraced) | **Trivial Docks Renown + Pattern L Red Hand City Action** (destroy controlled → claim → each player discards). Traits Villainous+Purge (`Purge` added to TraitNames). |
| `modules/php/cards/bas/actions/Action_04005.php` | Red Hand gate; destroy **target** (not performer); unequip before destroy; claimability filter; ActionResolved before multi-discard Transition. |
| `modules/php/States/bas/State_highDramaPhase04005.php` | HD destroy pick; `"back" => DISPATCH` (not bare CHOOSE_PERFORMER); named `"characterChosen"`. |
| `modules/php/States/bas/State_highDramaPhase04005_2.php` | Concurrent multi-discard for **each player** (incl. acting); hand-filter via `getGameDeckObject`; not sans-initiating. |
| `modules/php/cards/_7s5s/actions/Action_01095b.php` | Opponents-only multi-discard contrast (`MULTI_STATE_INITIATING_PLAYER` + sans-initiating). ActionResolved-before-Transition priority ordering. |
| `modules/php/cards/_7s5s/actions/Action_01015.php` | Scheme destroy parallel — destroys the **performer** as cost (opposite of `_04005` target destroy). |
| `modules/php/cards/bas/_04014.php` (Forged for Battle) | **Fixed Docks Renown + pick-another-location resolve + Continuous challenge/intervene Reaction.** Initiative 45 / Panache 0 / Zeal+Prepared verified against art. |
| `modules/php/cards/bas/reactions/Reaction_04014.php` | Continuous: `EventChallengeIssued` + `EventCharacterIntervened` → engage Weapon/Armor → +1 Finesse + `FORGED_FOR_BATTLE_CONDITION`. Clear on `EventActionResolved` `!IN_DUEL`. No `setUsed(true)` (comment literal). Skip `FakeAttachment`. |
| `modules/php/States/bas/State_planningPhaseResolveSchemes04014.php` | Planning resolve: city location pick excluding Docks (`actFromCardWithLocations`). |
| `modules/php/cards/_7s5s/_01089.php` (Soline) | Finesse condition Started/Ended tooltip pattern mirrored by Forged for Battle. |
| `modules/php/cards/_7s5s/reactions/Reaction_01040.php` (Rena) | Character Continuous engage-Weapon-instead on intervene — sibling of `_04014` Continuous discipline. |
