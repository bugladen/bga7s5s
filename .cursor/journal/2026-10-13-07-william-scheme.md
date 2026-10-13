# _05William Syrneth Secrets — Scheme implementation

## Printed text (scaffold Text property)

1. Add Renown to Docks + Bazaar.
2. Then move your Academic to a City location. If available Artifact there, may equip to performer paying all costs.
3. City Action: Engage Artifact on performer + target opposing non-Leader → Move them Home.

Test CAD — no clienttranslate / `_()` / `$game->translate` (same as Overcoat/Dabney).

## Classification

| Clause | Pattern |
|---|---|
| Fixed dual Renown Docks+Bazaar | Pattern A trivial |
| Then move Academic to City | Pattern A contingent Then — `_04004` Blood Money (Academic not Duelist) |
| May equip available Artifact paying costs | Extra planning states after move (01167-style pay); Pass legal |
| City Action Engage Artifact → Home | SchemeCityAction; two HD picks |

## WHY decisions

- **CardNumber=10 / keys `05William`:** CAD named-card routing like Overcoat (`405009` / `"05Overcoat"`). State ids `2605010*` / `405010*`.
- **Equip after move via LOW_PRIORITY transition:** CardMoving only queues CardMoved (both MEDIUM). A MEDIUM equip transition FIFO-ran *before* CardMoved → state 3 args used stale performer Location → empty `ids`, Artifact visible but not selectable. LOW waits for CardMoved. Also stash `CHOSEN_LOCATION` for args/validation.
- **CHOSEN_PERFORMER for Academic before equip:** pay discount / attach target need performer; planning character pick still uses CHOSEN_CARD between Academic→location (`_04004` discipline). After location chosen, copy Academic → CHOSEN_PERFORMER so pay flow matches 01167.
- **Affordability filter on offer:** only list Artifacts player can pay (hand wealth ≥ cost−discount). Avoid dead picks.
- **No Back after move:** Pass on optional equip only — move already committed via EVENTS.
- **City Action:** pick unengaged equipped Artifact first, then opposing non-Leader at performer location. Engage attachment (not performer). Move Home engage=false.
- **Plain strings:** user asked no translates for this test card only.

## Done

Implemented. `php -l` clean; full `php tests/run.php` green (CAD not covered).

## Pay UI (follow-up)

State 4 originally copied 01167_4 chooseList (discard-sourced attachment). City Artifacts are already on the board — switched to equip-from-play highlight (`highDramaEquipActionPayForAttachmentFromPlay`): performer + attachment `_7sfs-chosen`, wealth chip on city card, hand pays. No chooseList.

## City Action Artifact pick UX

`highDramaPhase05William`: switched from board-selectable attachments to named buttons (`attachments` id+name). Attached cards are hard to click under the host; instant `actFromCardWithId` (GaryFlipped / 01194 idiom). Performer still highlighted.

## Crystal Eye equip during scheme resolve

Confirm pay on Crystal Eye → Forced queues `"01200"` while still on `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` (260). Same class of bug as Kaj Approach equip (Approach had its own 01200 pair; resolve schemes did not). Added `PLANNING_PHASE_RESOLVE_SCHEMES_01200{,_2}` returning to resolve EVENTS; wired `_01200` args/acts + JS.

## Skill fold-in

Updated create-scheme with William learnings:
- SKILL shape table: optional equip-after-move row + Pattern O City Action; mega-paragraph `_05William`
- pattern-a: "Move Trait + optional equip available Artifact" (LOW_PRIORITY / CHOSEN_LOCATION / Crystal Eye resolve map)
- actions: Pattern O (engage Artifact buttons → Home)
- checklist 53–56; helpers traps; references; walkthrough; wiring CAD + Forced-on-resolve-equip

## Unfinished

No CAD regression suite yet.
