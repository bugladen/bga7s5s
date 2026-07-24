# _04cd19 Blood in the Water (bas City Event)

## Card Text
- **Forced:** When this card is revealed • Add a Renown to this location.
- **Forced:** After a character at this location becomes engaged • Wound them.

## Classification
Both clauses are **pure Forced** (no player choice) → Pattern A only. No Action/Reaction/State/JS. Mirror `bas/_04cd07` Festival of Fools (dual Forced on the card class).

## Implementation
`_04cd19.php` `handleEvent` only:

1. **Reveal** — `EventCityCardAddedToLocation` + `cardId == $this->Id`. Queue `createRenownAddedToLocationEvent` with `$event->location` (not `$this->Location`). playerId = `ControllerId` (0 for city events; EventHub ignore it for the actual add).
2. **Engage wound** — `EventCardEngaged` + `!canceled` + `cardInCity($this)` + `getCharacterById` non-null + `Location == $this->Location`. Queue `createCharacterBeingWoundedEvent` (1 wound).

## WHY
- Reveal gate without `cardInCity`: same as `_04cd07` / pattern-a — card is mid-placement when listening; identity is `cardId`.
- `$event->location` for renown target: skill explicitly says use event location for reveal Forced that needs a place; EventHub sets `$this->Location` before cards for this event (`runEventHubAfterCards=false`), but event field is the durable source of truth.
- Character filter via `getCharacterById`: `EventCardEngaged` also fires for attachments (e.g. Puzzle Box engages itself). Text says "a character" — do not wound attachments.
- `!$event->canceled`: EventCardEngaged runs cards before EventHub (`runEventHubAfterCards=true`). Impervious cancelers (Maryam) set canceled in the same pass; checking avoids wounding when engage was already canceled earlier. Legion's Caress (`_01021`) does not check — we do because "After becomes engaged" should not fire on a canceled engage. Residual race if we process before the canceler still exists codebase-wide; not inventing a post-hub follow-up event.
- Wound via queued `createCharacterBeingWoundedEvent` not direct mutate — same as `_03cd05` / `_01021`.
- No State/JS: pure Forced. Do not invent City Action scaffolding.

## Alternatives considered
- Listening after engage is applied: no separate post-hub engage-done event; reactions/Forced peers all listen on `EventCardEngaged` itself.
- Treating engage Forced as interactive: text has no choice.
- Skipping canceled check to match Legion's Caress exactly: worse for "After" wording when cancelers exist.

## Status
php -l clean. Not playtested on BGA Studio. Skill updated.

## Skill update (same day)
Folded `_04cd19` lessons into `create-city-event-card`:
- SKILL shape table: engage trigger + `_04cd19` as second pure dual-Forced exemplar
- pattern-a: engage gate row; `$event->location` on reveal effects; engage template; `runEventHubAfterCards=true` canceled note
- sub-patterns: reveal→`$event->location`; "Add a Renown to this location"; "Wound them"; full "After a character … becomes engaged" section (Character filter / canceled WHYs)
- references + checklist: matching gotchas

WHY document now: next agent will otherwise wound attachments on engage, use `$this->Location` on reveal Renown, skip `!$canceled`, or invent Action/State scaffolding for pure Forced.

## Gotchas for next agent
- Do not add `cardInCity` to the reveal branch.
- Do not wound on attachment engages at this location.
- Renown EventHub already notifies "ADDED … from inject"; we also announce Forced for parity with `_04cd07` spectator clarity (slightly redundant — leave both unless Eddie wants quieter).
