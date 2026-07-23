# _04cd07 Festival of Fools (bas City Event)

## Card Text
1. Forced: When this card is revealed • Each player draws a card.
2. Forced: At the end of High Drama • Each player with a character at this location that does not control this location draws a card.

## Classification
Both clauses → Pattern A (Forced on card `handleEvent`). No Action/Reaction/State/JS.

## WHY choices
- Reveal trigger = `EventCityCardAddedToLocation` + `$event->cardId == $this->Id`, mirror `_03cd13` Crabs in a Bucket. WHY: city events "reveal" by being placed at a city location; same event as every other Forced-on-reveal city event. No separate EventCardRevealed for city cards.
- End HD trigger = `EventHighDramaPhaseEnd` + `cardInCity($this)`, mirror `_03cd12` Equal Claim. WHY: established "at the end of High Drama" Forced pattern for city events still in play.
- Eligibility for HD draw: player has ≥1 character at `$this->Location` AND `$playerId != $location->Controller`. WHY: "with a character at this location that does not control this location" — character presence AND non-controller. When location is uncontrolled (`Controller == 0`), every player with a presence qualifies (nobody controls it). Controlling player never draws even if they have characters there.
- Draw via `EventFactory::createCardDrawnEvent` + queueEvent, same as `_03cd13`. Not a direct deck draw — keeps EventHub/notify path consistent.
- No early return when location uncontrolled on HD Forced (unlike `_03cd12` which only cares about flipping control). WHY: uncontrolled still means non-controllers with presence draw.

## Status
Done. `php -l` clean. CRLF preserved via Python write (StrReplace choked on line endings). No Action/Reaction/State/JS — both clauses Forced.

## Checklist
- Reveal Forced → EventCityCardAddedToLocation branch ✓
- End HD Forced → EventHighDramaPhaseEnd + cardInCity + non-controller with presence ✓
- Draws via createCardDrawnEvent queue ✓
- Notify only when at least one player qualifies on HD Forced (drewAny) ✓

## Feel
Straightforward Forced card. The only judgment call was uncontrolled locations: treating Controller==0 as "nobody controls it" so every present non-controller draws feels correct to the printed text and matches how isControlled() works elsewhere. Not playtested on Studio yet.

## Skill update (same day)
Folded _04cd07 learnings into `create-city-event-card`:
- **pattern-a.md**: location-guard table (reveal ≠ cardInCity), multi-Forced branches, draw-queue refs
- **sub-patterns.md**: "When this card is revealed", queue draws, non-controller+presence eligibility (WHY not to copy Equal Claim's uncontrolled early-return), end-HD refs include _04cd07
- **references.md**: row for `bas/_04cd07`
- **SKILL.md** / **checklist.md**: bas paths, pure-Forced stop rule, reveal/uncontrolled checklist traps

WHY update the skill now: the Equal Claim early-return and blanket `cardInCity` guidance would have caused a wrong Festival of Fools if a future agent mirrored _03cd12 literally for "does not control" text. Codifying the trap is the whole point of the journal→skill loop.
