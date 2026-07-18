# Insightful (_03059) Maneuver research

## Task
Research-only for implementing Maneuver on Risk Insightful. Text: look top 3 adversary deck, reveal one, add its Parry OR Thrust to this card, replace in any order; if participant Academic, may sink any instead of replacing.

## WHY the composition (not one mirror)
No single card does look+reveal+steal-combat-stat+reorder+conditional Academic sink. Closest pieces:
- **Calc timing is the landmine.** Adding Parry/Thrust to "this card" this round = Pattern C.3 (`Maneuver_03024`/`03035`). MUST `EventManeuverActivated` + `stackEvent`, NOT `EventResolveManeuver` (calc already ran). Documented in pattern-c.md and 03035 WHY comments — future agent will be tempted to copy `Maneuver_01077` resolve hook because it's also "maneuver + deck chooseList" and that would silently break the bonus.
- **Deck UI** = `Reaction_03052` (private look, chooseList, reorder) + `_02005`/`Action_02002` (sink then reorder). Prefer **direct named transitions** between GameStates (03052 style) after one stackEvent into state 1 — avoids the multi-step C.3 footgun where every intermediate return to EVENTS needs another stackEvent.
- **Adversary deck accessor** = `getCardsOnTopOfPlayerFactionDeck` (Technique_01010 / DeckTrait). Auto-reshuffles discard if short; may still return <3.
- **Reveal** = notify message, not a factory event. No `createCardRevealedEvent` for faction peeks.
- **Academic** = participant `hasTrait("Academic")` unlocks optional sink (0+). Not availability. Contrast `_03041::controlsAcademic` which is "controls an Academic" for scheme Forced.

## Parry/Thrust
`$card->Parry` / `$card->Thrust` on `IFactionCard` (Risk, FactionAttachment). Characters in faction deck are NOT IFactionCard → treat as 0. Apply via `$event->parry`/`thrust` in CalculateManeuverValues, don't permanently mutate the Risk's printed stats.

## Availability <3
Gate only on deck+discard > 0. Operate on actual N returned. Skip reorder UI when ≤1 remaining (03052 pattern).

## Wiring
States `52503059` (+_2/_3/_4), entries under DUEL_RESOLVE_MANEUVER_EVENTS (+ technique events mirror for Miyato), GameStates in States/faf/, JS in On*.faf.js. Full writeup: `_results/03059-insightful-maneuver-research.md`.

## Feelings
This is a chunky Maneuver — four UI steps if Academic. The C.3 timing call is the one that will save a debugging session. Academic sink being optional ("any") differs from 02005's mandatory ≥1 — don't copy that validation blindly.
