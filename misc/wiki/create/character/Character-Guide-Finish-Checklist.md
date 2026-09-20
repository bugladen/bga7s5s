# 10 — Finish checklist

← [[09 — Wiring|Character Guide Wiring States And JavaScript]] · [[Index|Implementing a Character Card]] · Next: [[11 — Examples|Character Guide Example Characters To Copy]]

Use this before you call a Character "done." Check only the rows that apply to what you built.

## Card class

- [ ] `extends Character` (not `Leader`, not `CityCharacter`)
- [ ] `initializeFaction(...)` is called; spelling is `Ussura` if used (not `Usurra`)
- [ ] No `CrewCap` / `Panache` / mandatory `"Leader"` trait (those are Leader-only)
- [ ] Stats match the card; dashed stats use `Dashed*` + `0`
- [ ] `CardNumber` matches the filename
- [ ] `resetCard()` is called after stats
- [ ] Actions / Reactions / Techniques / Maneuvers arrays match the printed text
- [ ] Techniques assigned without re-declaring `IHasTechniques` / `TechniqueTrait`
- [ ] Any `handleEvent` override calls `parent::handleEvent($event)` first
- [ ] Phase / ongoing listeners guard with `ControllerId` and `! characterIsInDiscardOrLocker($this)`

## Classification

- [ ] Every printed clause maps to exactly one pattern
- [ ] "City" abilities gate `cardInCity` and still use `Character`
- [ ] `IAbilityThatTargetsCharacters` only if text says **target**
- [ ] "Opposing" uses same location + different controller
- [ ] En Garde treated as precondition (`!$Engaged`) unless Engage is a printed cost
- [ ] Muster text hooks **both** `EventCharacterMustered` and `EventApproachCharacterPlayed`

## Actions

- [ ] `isAvailableToPlayer` gates are complete (no dead menu entries)
- [ ] No `setUsed` / `resetPlayerPassCount` / `announceAction` in the CharacterAction
- [ ] Terminal path calls `createActionResolvedEvent` (or challenge flow owns it)
- [ ] Event factories use `$owner->Id` for int `sourceId`, `$this->Id` for ability id
- [ ] Picker id arrays use `array_values(...)`
- [ ] Dual Actions split into `a` / `b` classes

## Reactions

- [ ] `isAvailable()` gated in `handleEvent`
- [ ] Identity field matches the event (read the event class)
- [ ] Valid-target precondition before prompting
- [ ] Pass does not `setUsed`; accept does (unless Continuous)
- [ ] Literals `$this->setUsed(` and `$this->isAvailable(` present (comment OK for Continuous)
- [ ] `ISorcererAbility` only if printed **Sorcerer** Reaction — then start + played events

## Techniques / Maneuvers

- [ ] Duel Techniques gate actor-is-owner when required
- [ ] Gambling Techniques gate `IN_DUEL` **and** `DUEL_GAMBLED`
- [ ] Character card does not re-declare `TechniqueTrait`
- [ ] Picker Techniques use `createTechniqueTransitionEvent` when appropriate
- [ ] Extra JS (`EventHandlers`) for chooseList multi-select / reorder if used

## Challenge Actions

- [ ] Engagement trichotomy chosen deliberately (Engage / conditional / never)
- [ ] `"NNNNN"` and `"NNNNN_2"` both in `states.inc.php` when using the standard hand-off
- [ ] New challenge type: PHP const int == JS int
- [ ] Intervene/refuse wired on server **and** client when the type restricts them
- [ ] Full no-intervene types also skip `Reaction_02058`
- [ ] Character-scoped refuse did **not** invent a type

## States + JS

- [ ] State class + `States.php` constant + `states.inc.php` entry
- [ ] `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState` for each new state
- [ ] Location pickers use `actFromCardWithLocations` + `resetCityLocations`
- [ ] Hand pickers use `factionHand`, not in-play highlighting

## Sanity

- [ ] `php -l` on every touched PHP file
- [ ] Mentally run the pre-commit table in `CLAUDE.md`
- [ ] Mirrored a real reference Character instead of inventing structure
- [ ] Journal entry written with the **why** of non-obvious choices (project convention)

## Next

Pick a mirror card → [[11 — Examples|Character Guide Example Characters To Copy]]
