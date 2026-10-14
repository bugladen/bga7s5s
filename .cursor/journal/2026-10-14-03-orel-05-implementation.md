# _05Orel — Character implementation

## Printed text

1. Cannot be included in Approach decks.
2. Cannot be equipped; cannot perform abilities on risks and schemes.
3. During pressures at Orel's location, +1 to your total. If you initiated, succeed even if tied.

Test CAD card — no `clienttranslate` / `_()` on card or card-specific notifies (user request).

## Classification

| Clause | Pattern |
|---|---|
| Approach ban | `canEnterApproachDeck()` → false (Damned); DeckValidator via predicate |
| Cannot be equipped | New `Character::canReceiveAttachments()`; Attachment::canAttachTo + hasEquipRestrictions + equip performer lists + eventCheckCore on Equipped |
| Cannot perform Risk/Scheme abilities | New `Character::canPerformRiskAndSchemeAbilities()`; RiskAction filter + SchemeAction parent filter + Theah::actionAvailableToPerformer + in-hand/in-play args |
| Pressure +1 / win ties if initiator | Pattern A EventPressureOccuring — Valeri flag shape but **not Covert** (no uncontrolled gate). Two bits: `OREL_PRESSURE_TYPE` (+1) always; `OREL_WIN_TIES_PRESSURE_TYPE` only when `$event->playerId == ControllerId` |

## WHY decisions

- **Two pressure bits, not one:** If a single bit both added +1 and won ties, an *opponent* pressuring at Orel's site would also win ties. Text: win ties only for the initiator who is Orel's controller.
- **Not Covert:** No uncontrolled gate on +1 or standing Influence chips. Valeri sibling is Covert-only; do not reuse `VALERI_PRESSURE_TYPE`.
- **Silence lifts equip / Risk-Scheme bans:** `canReceiveAttachments` / `canPerformRiskAndSchemeAbilities` return `$this->abilitiesAreBlanked()` so blanked text lifts the restriction. Approach ban stays always-false (deck rule / Damned shape).
- **Framework hooks on Character:** Prefer predicates over Orel-only instanceof sprinkles in every equip/performer path. DeckValidator now uses `canEnterApproachDeck()` (drops Damned hardcode; Brute keeps trait message).
- **Novel traits Eagle + Familiar** → TraitNames. Animal already present. No faction-nation trait (scaffold omitted Ussura in Traits list).

## Files

- `_05Orel.php` — predicates + pressure handleEvent
- `Character.php` — `canReceiveAttachments` / `canPerformRiskAndSchemeAbilities` + Equipped eventCheckCore
- `Attachment.php`, `UtilitiesTrait` (hasEquipRestrictions + pressureLocation + standing chips), `RiskAction`, `SchemeAction`, `Theah`, `ArgumentsTrait`, `FrameworkActionsTrait`, `DeckValidator`, `Game.php` constants, `TraitNames`

## Unfinished / watch

- No CAD regression suite. Playtest: Approach deck reject; Equip picker hides Orel; Risk performer picker hides Orel; pressure at his site +1 both ways; win-ties only when his controller initiates; opponent initiate gets Orel controller +1 but no win-ties for opponent.

## Skill fold-in (same day, user-requested)

Updated create-character skill with Orel learnings:
- SKILL shape table: Approach ban / cannot-be-equipped / cannot-perform-Risk-Scheme / non-Covert +N + initiator win-ties rows
- pattern-a: "Approach / equip / Risk-Scheme host bans"; "Non-Covert pressure aura + initiator win-ties"; EventPressureOccuring row notes `playerId` initiator
- checklist 101b + 110–112; CAD id note 103 extended for Orel
- references `_05Orel`; helpers pressure + Character predicates

WHY two pressure bits documented explicitly: future agents will otherwise merge +N and win-ties onto one Valeri-like flag and break opponent-initiated pressures.

## Pre-commit false positive (2026-10-14)

Commit aborted: `RiskAction.php` / `SchemeAction.php` flagged for missing `createActionResolvedEvent()`.

WHY it fired: Orel work added `getPerformersForAction` filters on those bases. Hook check #3 matches `extends CardAction|RiskAction|RiskCityAction`. Intermediate bases in `modules/php/cards/actions/` match that but never resolve actions.

Fix: `.githooks/pre-commit` skips `modules/php/cards/actions/*` for that check (same idea as EventFactory/tests skips). Verified with Git Bash — passes. Include the hook file when re-committing.
