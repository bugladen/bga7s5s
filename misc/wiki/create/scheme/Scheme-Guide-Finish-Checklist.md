# 10 — Finish checklist

← [[09 — Wiring|Scheme Guide Wiring States And JavaScript]] · [[Index|Implementing a Scheme Card]] · Next: [[11 — Examples|Scheme Guide Example Schemes To Copy]]

Use this before you call a Scheme "done." Check only the rows that apply to what you built.

## Card class

- [ ] `extends Scheme`
- [ ] `initializeFaction(...)` is called; spelling is `Ussura` if used (not `Usurra`)
- [ ] `Initiative` is set and non-zero
- [ ] `PanacheModifier` is set (often `0`, `+1`, or `-1`)
- [ ] `CardNumber` matches the filename
- [ ] Traits match the JPG and exist in `TraitNames::$TraitsJson`
- [ ] `resetCard()` is called after fields
- [ ] Actions / Reactions arrays match the printed text
- [ ] Any `handleEvent` override calls `parent::handleEvent($event)` first
- [ ] No Character/Leader-only fields (`Resolve`, `CrewCap`, `Panache`, mandatory `"Leader"` trait)

## Classification

- [ ] Every printed clause maps to exactly one pattern
- [ ] Text above `<hr>` is resolve / When-Revealed — not a City Action
- [ ] "Spend a Renown" uses player score events; location Renown uses location events
- [ ] Trait prefixes (Strega / Hero / …) are performer `hasTrait` gates — not `ISorcererAbility` unless printed **Sorcerer**
- [ ] `IAbilityThatTargetsCharacters` only if text says **target**
- [ ] Chosen-scheme gates use `LOCATION_PLAYER_HOME` (not discard)

## Resolve

- [ ] `EventResolveScheme` identity-checks `$event->scheme->Id == $this->Id`
- [ ] Auto Renown queued before player-pick transitions; pick transitions use `MEDIUM_PRIORITY`
- [ ] Player-choice resolve states registered under **`PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS`** (`26<NNNNN>`)
- [ ] Two-location picks also update `PlayerActions.js` `actionMap`
- [ ] Discard Pass throws when an eligible card remains (unless text says otherwise)

## When-Revealed / Forced / Passives

- [ ] When-Revealed: `hasWhenRevealedEffect()` returns `true` **and** `EventCardWhenRevealedEffect` handled
- [ ] Planning-End Forced: `EventPhasePlanningEnd` + Home gate; picks under **`PLANNING_PHASE_END_EVENTS`** (`28<NNNNN>`)
- [ ] HD-End Forced: `EventHighDramaPhaseEnd` + Home gate; picks under **`HIGH_DRAMA_END_EVENTS`**
- [ ] Draw-then-discard clamps to drawable; 0 drawable skips discard state
- [ ] Equip +1 cost passive uses `getEquipDiscount` -= 1 with Home + **`cardInCity($performer)`** gates

## Actions

- [ ] Extends `SchemeCityAction` (or correct scheme base) — not `CharacterAction`
- [ ] `isAvailableToPlayer` / `getPerformersForAction` gate the **whole** action (no dead menu entries)
- [ ] Claim-as-payoff filters `canLocationBeClaimedBy`
- [ ] No `setUsed` / `resetPlayerPassCount` / `announceAction` in the Action
- [ ] Terminal path calls `createActionResolvedEvent` (or challenge flow documents ownership)
- [ ] Action field changes call `$game->updateCardObjectInDb($owner)`
- [ ] Immediate-resolve shapes did **not** invent an unnecessary HD GameState
- [ ] HD action picks registered under **`HIGH_DRAMA_PLAYER_TURN_EVENTS`** (`40<NNNNN>`)

## Reactions

- [ ] `isAvailable()` gated in `handleEvent`
- [ ] No false "scheme left play" guards
- [ ] Event-time context captured on the Reaction; cleared after resolve
- [ ] Pass does not `setUsed`; accept does (unless Continuous)
- [ ] Literals `$this->setUsed(` and `$this->isAvailable(` present
- [ ] `ISorcererAbility` only if printed **Sorcerer** — then start + played events
- [ ] Cross-player steps use Reaction `$stage` + `createReactionTransitionEvent` — not a random HD state

## Challenge Actions

- [ ] Engagement shape chosen deliberately
- [ ] Custom `CHALLENGE_TYPE` wired in Theah + ArgumentsTrait + Reaction_02058 when intervene is restricted
- [ ] Discard-to-refuse uses named success transition (no `""` + `"back"`)
- [ ] PHP challenge type int matches JS when the client gates Refuse/Intervene

## States + JS

- [ ] GameState class + `States.php` constant + correct `states.inc.php` map entry
- [ ] No extra `ZombieTrait.php` case for new GameState-class states
- [ ] `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState` for each new state
- [ ] Location pickers clean up with `resetCityLocations()`
- [ ] Hand multi-discard also updates `EventHandlers.js` exact-count enable

## Sanity

- [ ] `php -l` on every touched PHP file
- [ ] Mentally run the pre-commit table in `CLAUDE.md`
- [ ] Mirrored a real reference Scheme instead of inventing structure
- [ ] Journal entry written with the **why** of non-obvious choices (project convention)

## Next

Pick a mirror card → [[11 — Examples|Scheme Guide Example Schemes To Copy]]
