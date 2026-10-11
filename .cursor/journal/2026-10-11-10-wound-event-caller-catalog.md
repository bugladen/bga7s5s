# Wound event caller catalog (2026-10-11)

Built full catalog of `EventFactory::createCharacterBeingWoundedEvent` callers from the user-supplied file list (93 files). Raw extracts in `_catalog_raw.txt` / `_wound_extract.txt`.

## Wound application path

- `createCharacterBeingWoundedEvent` → `EventCharacterBeingWounded`
- EventHub: if wounds > 0, queues `EventCharacterWounded`
- `Character::handleEvent(EventCharacterWounded)`: `$this->Wounds += $event->wounds`

Other `$character->Wounds =` sites are NOT damage: flip state copy (`_05Gary`), heal reset, EventHub clear, end-game tiebreak (`Wounds = 100` for destroyed leaders). Only damage increment is via `EventCharacterWounded`.

## Callers NOT in user list (6 extras, 100 total excl. EventFactory)

- `Action_01019.php`, `Maneuver_01079.php`, `Reaction_01137.php`, `bas/_04cd14.php`, `Action_03026.php`, `Action_03062.php`

## Classification notes for future

- SELF = performer/owner/attached/participant cost wound on controller's own character
- ANOTHER = adversary/target/opponent/other character (incl. friendly ally costs like "wound your other Red Hand")
- BOTH = multi-target including self+others in one ability
- CONDITIONAL = may or may not wound / player choice / gate on board state
- Reaction_04058 factory call is Cesca-copy synthetic; main path redirects cloned intercepted wound without a second factory call
