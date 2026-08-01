# Recruit crew-cap warnings for Cirilo / Kaspar / Filling the Ranks

## Problem

Basic Recruit already warns via `basicRecruitActionCrewCapCheck` → confirmationDialog when recruiting would exceed Crew Cap. Other recruit paths skipped that UX:

- **Action_01035 (Kaspar)** — in-play action choose button (`Action_01035`)
- **Reaction_01144 (Filling the Ranks)** — `fillRanks` reaction button

(Cirilo Action_01009 listed initially, then skipped — recruits become Brutes.)

## Approach / WHY

Client-side only, matching basic Recruit. Generalized to `withCrewCapWarning(callback)` so every path shares one dialog string and one check.

Hook points chosen to match "warn before you commit, while cancel/pass is still available":

| Path | Hook | WHY |
|---|---|---|
| Kaspar | `highDramaInPlayActionChooseAction` when `action.id` contains `Action_01035` | Eddie: warn when the action button is pressed (before engage/reveal). Cancel still available on confirm. |
| Fill Ranks | `button.reaction === 'fillRanks'` | Unique button id; Pass still available in merc-choose state |

## Also fixed: Brute exclusion in `crewCapCheck`

Prior Cirilo audit (`2026-04-10-12`) noted JS counted Brutes toward cap. Backend `getCharacterCountByPlayerId` excludes them. Fixed filter to skip `traits.includes('Brute')`. WHY: false "you'll exceed" when you already have Brutes in play.

## Cirilo skipped (Eddie follow-up)

Cirilo's passive grants Brute to recruited Mercenaries *before* `Reaction_CrewCapLimit` runs (cards handleEvent before theah reactions). Recruit via Action_01009 never forces a crew-cap sink, so warning would be a false positive. Eddie confirmed: skip Cirilo.

## Kaspar hook moved (Eddie follow-up)

Initially warned on Recruit at `01035_3`. Eddie: warn when Kaspar's action button is pressed instead. Moved to `highDramaInPlayActionChooseAction` for `Action_01035`; removed wrap from Recruit.

## Files

- `modules/js/Utilities.js` — Brute exclude + `withCrewCapWarning`
- `modules/js/OnUpdateActionButtons.js` — fillRanks + Kaspar action-choose button
- `modules/js/OnUpdateActionButtons.7s5s.js` — no Kaspar Recruit wrap (moved to action choose)
