# Meeting of the Minds (_04035)

Implementing BAS scheme via create-scheme skill.

## Classification
1. **Resolve (Pattern A):** Add Renown to Bazaar + Forum. If control an Academic, put non-Revelry Risk from discard OR Locker into hand.
2. **Reaction:** When pressure occurs at your performer's location → +1 to your total for each of your Academics there.

## Art vs scaffold
- Initiative 83 / Panache -1 / Discovery — match art. Traits already in TraitNames.
- Text matches art (Bazaar+Forum icons, Academic gate, non-Revelry risk from discard/Locker, pressure Reaction).

## Design notes / WHY
- Academic risk pick is **contingent**: only transition if player controls ≥1 Academic AND has ≥1 eligible Risk in discard or locker. Mirror contingent "Then" discipline (`_04004`), not `_03005`'s always-open discard state.
- Combined discard+locker chooseList (not button-per-card like `Reaction_02032`) — can be many Risks; reuse `_03005`/`_03062` chooseList UX. Pass `ids` from args for JS filter (coerce Number like locker muster).
- Move from discard vs locker: branch on `$card->Location` like `Reaction_04003a::moveThugFromDiscardOrLockerToHand`.
- Mandatory pick when eligible (Pass throws) — printed text has no "may".
- Reaction gate: `$event->playerId == owner->ControllerId` — "your performer" = you are the pressuring player. Require ≥1 Academic at location before offering (else +0).
- Pressure bonus: new `MEETING_OF_THE_MINDS_PRESSURE_TYPE` + player id global (Loyal/Vantage Point shape). Count Academics at calc time from fresh `getCharactersAtLocation` — not the Claude/Reputation-filtered list — because text says Academics *there*, not Academics that count toward pressure stats.
- Once-per-day Reaction (`setUsed` on success; Pass does not).

## Files shipped
- `cards/bas/_04035.php`, `reactions/Reaction_04035.php`
- `States/bas/State_planningPhaseResolveSchemes04035.php`
- `States.php` 2604035; `states.inc.php` `"04035"` under resolve map
- `Game.php` MEETING_OF_THE_MINDS_PRESSURE_TYPE (65536) + PLAYER_ID
- `UtilitiesTrait::pressureLocation` Academic bonus; `StatesTrait` cleanup delete
- JS bas triple: combined discard+locker chooseList

## Skill update
Captured into create-scheme: contingent Academic discard/Locker Risk pick (pattern-a), pressure +1-per-Trait Reaction (reactions + SKILL shape table + references).

## Status
Done. php -l clean on touched PHP. Unfinished: none for this card.
