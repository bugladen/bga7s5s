# 10 — Finish checklist

← [[09 — Wiring|CityCharacter Guide Wiring States And JavaScript]] · [[Index|Implementing a CityCharacter Card]] · Next: [[11 — Examples|CityCharacter Guide Example CityCharacters To Copy]]

Use this before you call a CityCharacter "done." Check only the rows that apply to what you built.

## Card class

- [ ] `extends CityCharacter` (not `Character`, not `Leader`, not `CityEventCard`)
- [ ] `CardNumber = 0` and `CityCardNumber` matches the printed city index
- [ ] `WealthCost` matches the card
- [ ] `Negotiable` matches the printed keyword (default false)
- [ ] No `initializeFaction(...)` / no `CrewCap` / no `Panache` / no mandatory `"Leader"` trait
- [ ] Stats match the card; dashed stats use `Dashed*` + `0`
- [ ] `resetCard()` is called after stats
- [ ] Actions / Reactions / Techniques / Maneuvers arrays match the printed text
- [ ] Techniques assigned without re-declaring `IHasTechniques` / `TechniqueTrait`
- [ ] Any `handleEvent` override calls `parent::handleEvent($event)` first
- [ ] Any `eventCheck` override calls `parent::eventCheck($event)` first

## Classification

- [ ] Every printed clause maps to exactly one pattern
- [ ] Hard bans use predicate **and** `eventCheck`
- [ ] City Forced / City Action / City Reaction gate city scope correctly
- [ ] "Would be wounded" vs "when wounded" event choice matches the verb
- [ ] "Participates in a duel" uses `EventDuelStarted` (not challenge-issued) when that is the print
- [ ] `IAbilityThatTargetsCharacters` only if text says **target**
- [ ] "Opposing" uses same location + different controller
- [ ] En Garde treated as precondition unless Engage is a printed cost

## Actions

- [ ] Extends `CharacterAction` (not `EventCityAction`)
- [ ] `isAvailableToPlayer` gates are complete (no dead menu entries)
- [ ] No `setUsed` / `resetPlayerPassCount` / `announceAction` in the CharacterAction
- [ ] Terminal path calls `createActionResolvedEvent` (or challenge flow owns it)
- [ ] Engage-as-cost uses `createCardEngagedEvent`; moves use `$engage = false` when appropriate
- [ ] Event factories use `$owner->Id` for int `sourceId`, `$this->Id` for ability id
- [ ] Picker id arrays use `array_values(...)`

## Reactions

- [ ] `isAvailable()` gated in `handleEvent`
- [ ] Identity field matches the event
- [ ] Move triggers use `$event->toLocation` (not `$owner->Location`) inside `handleEvent`
- [ ] Valid-target precondition before prompting
- [ ] Pass does not `setUsed`; accept does (unless Continuous)
- [ ] Literals `$this->setUsed(` and `$this->isAvailable(` present
- [ ] No unnecessary state / JS wiring for button-only Reactions
- [ ] `< Back` omitted after effect events have committed

## Techniques / Maneuvers

- [ ] Duel Techniques gate actor-is-owner when required
- [ ] Gambling Techniques gate `IN_DUEL` **and** `DUEL_GAMBLED`
- [ ] Card does not re-declare `TechniqueTrait`

## Challenge Actions

- [ ] Engagement trichotomy chosen deliberately
- [ ] `"03cdNN"` and `"03cdNN_2"` both in `states.inc.php` when using the standard hand-off
- [ ] New challenge type: PHP const int == JS int

## States + JS

- [ ] State class + `States.php` constant + `states.inc.php` entry
- [ ] Did **not** add new GameState entries to `states.7s5s.php`
- [ ] `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState` for each new state
- [ ] Location pickers use `actFromCardWithLocations` + `resetCityLocations`
- [ ] `PlayerActions.js` updated if reusing a client action

## Sanity

- [ ] `php -l` on every touched PHP file
- [ ] Mentally run the pre-commit table in `CLAUDE.md`
- [ ] Mirrored Penya / Julius / Kalla (or another real CityCharacter) instead of inventing structure
- [ ] Journal entry written with the **why** of non-obvious choices (project convention)

## Next

Pick a mirror card → [[11 — Examples|CityCharacter Guide Example CityCharacters To Copy]]
