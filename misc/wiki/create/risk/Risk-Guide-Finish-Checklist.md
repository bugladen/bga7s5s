# 10 — Finish checklist

← [[09 — Wiring|Risk Guide Wiring States And JavaScript]] · [[Index|Implementing a Risk Card]] · Next: [[11 — Examples|Risk Guide Example Risks To Copy]]

Use this before you call a Risk "done." Check only the rows that apply to what you built.

## Card class

- [ ] `extends Risk` (not Character, Scheme, or Attachment)
- [ ] `initializeFaction(...)` called (including Neutral)
- [ ] `CardNumber` matches filename digits; `WealthCost` set
- [ ] Riposte / Parry / Thrust match the print; `DashedX` set when printed dashed
- [ ] Traits exist in `TraitNames::$TraitsJson` (fix stub typos like `Beauracracy` → `Bureaucracy`)
- [ ] `resetCard()` after fields; Actions / Maneuvers / Reactions after that
- [ ] `IRiskThatTargetsCharacters` only when printed text says **Target** / **target**
- [ ] Any `handleEvent` override calls `parent::` first

## Classification

- [ ] Every printed clause maps to exactly one pattern
- [ ] City Action vs Action performer pools correct
- [ ] City Reaction still uses `RiskReaction` + city gate (no invented base)
- [ ] Trait prefixes gated with `hasTrait` / `DUEL_GAMBLED` — not `ISorcererAbility` unless "Sorcerer" is printed
- [ ] Literal wording traps checked (they/you claim, participant vs adversary wound, engage vs en garde, `>` vs `>=`, claim-control vs enemy character)

## City Actions / Actions

- [ ] Correct base: `RiskCityAction` or `RiskAction`
- [ ] Performers start from `parent::getPerformersForAction`
- [ ] Engage-cost Actions also filter `! Engaged`; Influence challenges also `! DashedInfluence`
- [ ] `createActionResolvedEvent` literal present
- [ ] No `setUsed` / `announceAction` / `resetPlayerPassCount` in the Action subclass
- [ ] Custom challenge side effects use a fresh `CHALLENGE_TYPE` correlator (events have no `actionId`)
- [ ] Engage-self + challenge keeps custom type off auto-engage list
- [ ] Sorcerer: start + played events both present
- [ ] Action-only Leader discount uses `getActionFromHandDiscount` (no invented Maneuver)

## Maneuvers

- [ ] Calc vs resolve split correct (multi-fire vs once)
- [ ] `EventManeuverCanceled` handler **or** `// EventManeuverCanceled handler not needed`
- [ ] Wound target is actor vs opponent per print
- [ ] Choice-at-activation uses `stackEvent` from Activated (not after Resolve)
- [ ] Dual a/b split when two distinct Maneuvers
- [ ] Neutral/Ussura transition mirrored under `DUEL_CHOOSE_TECHNIQUE_EVENTS` when needed
- [ ] Combat-card discounts on Maneuver with `$owner->Id == $combatCard->Id`

## Reactions

- [ ] `extends RiskReaction`
- [ ] Literals: hand `Location == Game::LOCATION_HAND`, `setUsed`, `isAvailable`
- [ ] Effect deferred to `EventRiskReactionTriggered` when pay can be canceled
- [ ] City Reaction: city-character gate
- [ ] Intervene text listens to `EventCharacterIntervened` (not ChallengeAccepted)
- [ ] Dual a/b split when two distinct Reactions
- [ ] Multi-stage: deferred `setUsed` until finalize; prefer reaction buttons over GameStates when pools are small

## Forced / discounts

- [ ] Forced with no choice stays on Risk `handleEvent`
- [ ] Duel-line Forced gates location + `IN_DUEL` + correct adversary
- [ ] Discount channel matches (Maneuver combat-card vs Action hand)

## States + JS

- [ ] Constant + state definition + `states.inc.php` transition entry
- [ ] GameState `nextState("namedKey")` matches transitions table
- [ ] Enter / Update / Leave JS for each new interactive state (skip for reaction-button-only flows)
- [ ] Zombie transition present

## Sanity

- [ ] `php -l` on every touched PHP file
- [ ] Mentally run the pre-commit table in `CLAUDE.md`
- [ ] Mirrored a real reference Risk instead of inventing structure
- [ ] Journal entry written with the **why** of non-obvious choices (project convention)

## Next

Pick a mirror card → [[11 — Examples|Risk Guide Example Risks To Copy]]
